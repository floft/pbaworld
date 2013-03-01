<?php
require_once "include.php";
site_header("Print");
?>
<script type="text/javascript">
<!--
window.loaded_options = false

function loadOptions(func, type) {
	var url     = "rpc.php?t=" + type
	var replace = get("replace")
	
	replace.innerHTML="Loading..."

	http(url, function(text) {
		var list = JSON.parse(text)

		replace.innerHTML=""
		replace.style.display="none"
		func(list)
	})
}

function loadChapters(book, chapters, func) {
	var questions;
	var replace = get("replace")

	var url = "rpc.php?t=q"
		+"&b="+book
		+"&c="+chapters

	replace.innerHTML = "Loading..."

	http(url, function(text) {
		var questions = JSON.parse(text)

		replace.innerHTML = ""
		replace.style.display="none"
		func(questions)
	})
}

function loadVerses(book, chapters, func) {
	var url     = "rpc.php"
		    + "?t=v&b=" + book
		    + "&c=" + chapters
	var replace = get("replace")
	
	replace.innerHTML="Loading..."

	http(url, function(text) {
		var list = JSON.parse(text)

		replace.innerHTML=""
		replace.style.display="none"
		func(list)
	})
}

function showSelect() {
	if (window.loaded_options) {
		get("select").style.display="inline"
	} else {
		window.loaded_options = true

		loadOptions(function(list) {
			for(var i=0; i<list.length; ++i) {
				var book	= list[i][0]
				var chapter	= list[i][1]

				var option = document.createElement("option")
				option.setAttribute("value", book + "-" + chapter)
				option.innerHTML = book + " " + chapter

				get("print_questions").appendChild(option)
			}
		}, "lq")
		
		for (var i=5; i<100; i+=5) {
			var option = document.createElement("option")
			option.setAttribute("value", i)
			option.innerHTML = i + "%"

			if (i == 10)
				option.setAttribute("selected", "selected")

			get("difficulty").appendChild(option)
		}

		loadOptions(function(list) {
			for (var book in list) {
				for (var i=0; i<list[book].length; ++i) {
					var chapter = list[book][i]

					var option = document.createElement("option")
					option.setAttribute("value", book + "-" + chapter)
					option.innerHTML = book + " " + chapter

					get("quiz_over").appendChild(option)
				}
			}
	
			get("select").style.display="inline"
		}, "lv")
	}
	
	get("print").style.display="none"
}

function showPrintQuestions() {
	var e	    = get("print_questions")
	var parts   = e.options[e.selectedIndex].value.split("-")
	var book    = parts[0]
	var chapter = parts[1]

	var o       = get("answer_location")
	var end     = (o.options[o.selectedIndex].value === "1")

	get("select").style.display="none"
	get("print").style.display="inline"
	get("print").innerHTML = "Loading..."

	loadChapters(book, chapter, function(questions) {
		var e = get("print")
		e.innerHTML = ""

		var commentary = (book == "Commentary")

		if (end) {
			var title = document.createElement("p")
			title.innerHTML="<b>Questions for "+book+" "+chapter+"</b>"
			e.appendChild(title)

			for (var i=0; i < questions.length; ++i) {
				var text     = document.createElement("p")
				var verse    = questions[i][1]
				var question = questions[i][2]

				if (commentary)
					text.innerHTML="<b>"+(i+1)+".</b> "+question
				else
					text.innerHTML="<b>"+(i+1)
						+". Verse "+verse+"</b><br />"+question

				e.appendChild(text)
			}
			
			var answers = document.createElement("p")
			answers.innerHTML="<b>Answers for "+book+" "+chapter+"</b>"
			e.appendChild(answers)
			
			for (var i=0; i < questions.length; ++i) {
				var text   = document.createElement("p")
				var verse  = questions[i][1]
				var answer = questions[i][3]

				if (commentary)
					text.innerHTML="<b>"+(i+1)+".</b> "+answer
				else
					text.innerHTML="<b>"+(i+1)
						+". Verse "+verse+"</b><br />"+answer

				e.appendChild(text)
			}
		} else {
			var title = document.createElement("p")
			title.innerHTML="<b>"+book+" "+chapter+"</b>"
			e.appendChild(title)

			for (var i=0; i < questions.length; ++i) {
				var text       = document.createElement("p")
				var verse    = questions[i][1]
				var question = questions[i][2]
				var answer   = questions[i][3]
				
				if (commentary)
					text.innerHTML="<b>Q:</b> "+question
						+"<br /><b>A:</b> "+answer
				else
					text.innerHTML="<b>Verse "+verse+"<br />"
						+"Q:</b> "+question+"<br /><b>A:</b> "+answer

				e.appendChild(text)
			}
		}

		var back      = document.createElement("p")
		var back_link = document.createElement("a")
		back_link.setAttribute("href", "#")
		back_link.onclick = function() {
			showSelect()
			return false
		}
		back_link.innerHTML = "Back"
		back.appendChild(back_link)
		e.appendChild(back)
	})
}

function showPrintFillIns() {
	var e	       = get("quiz_over")
	var parts      = e.options[e.selectedIndex].value.split("-")
	var book       = parts[0]
	var chapter    = parts[1]

	var o          = get("answer_location_fillin")
	var end        = (o.options[o.selectedIndex].value === "1")
	
	var d	       = get("difficulty")
	var difficulty = parseInt(d.options[d.selectedIndex].value)

	get("select").style.display="none"
	get("print").style.display="inline"
	get("print").innerHTML = "Loading..."
	
	loadVerses(book, chapter, function(list) {
		var e = get("print")
		e.innerHTML=""
		
		var result_verses    = new Array()
		var result_questions = new Array()
		var result_answers   = new Array()

		for (var q = 0; q < list.length; ++q) {
			var verse   = list[q][1]
			var text    = list[q][2]

			//create array of word indexes, choose one at random,
			//remove it from the array, choose another, etc. until
			//you reach the difficulty level percentage
			var words = intoWords(text)

			var terms = Math.floor(difficulty / 100 * words.length)
			var words_left   = new Array()
			var replace_list = new Array()
			var answers      = new Array()

			for (var i = 0; i < words.length; ++i)
				if (words[i].replace(/^\s+|\s+$/g, "") != ""		//blank
					&& goodWord(words[i])				//a, an, the
					&& words[i].replace(/[^a-zA-Z]/g, "") != ""	//"
					&& words[i].match(/^[^<]/))			//<br />
					words_left.push(i)

			if (words_left.length < terms)
				terms = words_left.length
			else if (terms == 0)
				terms = 1

			for (var i = 0; i < terms; ++i) {
				var index = Math.floor(Math.random() * words_left.length)
				replace_list.push(words_left[index])

				words_left.splice(index, 1)
			}

			replace_list.sort(function(a,b){return a - b})

			for (var i = 0; i < replace_list.length; ++i) {
				var word = words[replace_list[i]]
				words[replace_list[i]] = "________"
					+ word.substring(word.length-1, word.length)

				answers.push(word.substring(0, word.length - 1))
			}

			result_verses.push(verse)
			result_questions.push(words.join(""))
			result_answers.push(answers.join(", "))
		}

		if (end) {
			var title = document.createElement("p")
			title.innerHTML="<b>Questions for "+book+" "+chapter+" (NKJV)</b>"
			e.appendChild(title)

			for (var i = 0; i < result_verses.length; ++i) {
				var text = document.createElement("p")
				text.innerHTML = "<b>"+(i+1)
					+". Verse "+result_verses[i]+"</b><br />"
					+result_questions[i]
				e.appendChild(text)
			}
			
			var title2 = document.createElement("p")
			title2.innerHTML="<b>Answers for "+book+" "+chapter+"</b>"
			e.appendChild(title2)

			for (var i = 0; i < result_verses.length; ++i) {
				var text = document.createElement("p")
				text.innerHTML = "<b>"+(i+1)
					+". Verse "+result_verses[i]+"</b><br />"
					+result_answers[i]
				e.appendChild(text)
			}
		} else {
			var title = document.createElement("p")
			title.innerHTML="<b>"+book+" "+chapter+" (NKJV)</b>"
			e.appendChild(title)

			for (var i = 0; i < result_verses.length; ++i) {
				var text = document.createElement("p")
				text.innerHTML="<b>Verse "+result_verses[i]
					+"<br />Q:</b> "+result_questions[i]
					+"<br /><b>A:</b> "+result_answers[i]
				e.appendChild(text)
			}
		}

		var back      = document.createElement("p")
		var back_link = document.createElement("a")
		back_link.setAttribute("href", "#")
		back_link.onclick = function() {
			showSelect()
			return false
		}
		back_link.innerHTML = "Back"
		back.appendChild(back_link)
		e.appendChild(back)

		get("print").style.display="inline"
	})
}

window.onload=showSelect
// -->
</script>
<div class="title">Print Questions</div>
<div id="replace"></div>

<noscript><p>It appears that you don't have Javascript enabled. You will probably want to go to the <a href="html/print.php">HTML version</a> of this page.</p></noscript>

<div id="select">
<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="get" onsubmit="showPrintQuestions(); return false;">
<p>Please select the chapters you want and whether you want the questions to be displayed with the answers or at the end. Also, if you are printing the Fill in the Blanks questions, specify the percentage of the words that will be blank (or leave it at the default).</p>
<h3>Questions</h3>
<b>Quiz over</b> <select id="print_questions"></select><br />
<b>Answers</b>   <select id="answer_location">
	<option value="0">with questions</option>
	<option value="1">at the end</option>
</select><br />
<input type="submit" value="Display" />
</form>
<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="get" onsubmit="showPrintFillIns(); return false;">
<h3>Fill in the Blanks</h3>
<b>Quiz over</b> <select id="quiz_over"></select><br />
<b>Difficulty</b> <select id="difficulty"></select><br />
<b>Answers</b>   <select id="answer_location_fillin">
	<option value="0">with questions</option>
	<option value="1">at the end</option>
</select><br />
<input type="submit" value="Display" />
</form>

<p><b>Note:</b> at most the first 1000 verses of the book(s) being studied will be displayed.</p>
</div>

<div id="print"></div>

<?php site_footer("New King James Version&reg;, Copyright &copy; 1982, Thomas Nelson, Inc. All rights reserved."); ?>
