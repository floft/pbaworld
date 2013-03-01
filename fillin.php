<?php
require_once "include.php";
site_header("Fill in the Blanks");
?>
<script type="text/javascript">
<!--
window.loaded_options = false

function loadOptions(func) {
	var url     = "rpc.php?t=lv"
	var replace = get("replace")
	
	replace.innerHTML="Loading..."

	http(url, function(text) {
		var list = JSON.parse(text)

		replace.innerHTML=""
		replace.style.display="none"
		func(list)
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
		})
	}
	
	get("done").style.display="none"
	get("question").style.display="none"
}

function showQuiz() {
	get("done").style.display="none"
	get("select").style.display="none"

	var e       = get("quiz_over")
	var parts   = e.options[e.selectedIndex].value.split("-")
	var book    = parts[0]
	var chapter = parts[1]

	var f          = get("difficulty")
	var difficulty = f.options[f.selectedIndex].value

	window.position        = 0
	window.verses          = new Array()
	window.score_correct   = 0
	window.score_incorrect = 0
	window.book            = book
	window.difficulty      = difficulty

	loadVerses(book, chapter, function(list) {
		window.verses = list

		window.verses.sort(function() {
			return 0.5 - Math.random()
		})

		nextQuestion()
		get("question").style.display="inline"
	})

	get("end_title").innerHTML = book + " " + chapter
}

function nextQuestion() {
	var i = window.position

	window.needToConfirm = true
	
	get("form").onsubmit=function() {
		check()
		return false
	}

	if (i == window.verses.length) {
		showDone()
	} else {
		var list    = window.verses

		var chapter = list[i][0]
		var verse   = list[i][1]
		var text    = list[i][2]
		var title   = window.book + " " + chapter + ":" + verse

		//create array of word indexes, choose one at random,
		//remove it from the array, choose another, etc. until
		//you reach the difficulty level percentage
		var words = intoWords(text)

		var terms = Math.floor(window.difficulty / 100 * words.length)
		var words_left   = new Array()
		var replace_list = new Array()
		
		var answers      = new Array()
		var ids	         = new Array()

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
			words[replace_list[i]] = "<input type=\"input\" value=\"\" "
				+"id=\"answer_"+i+"\" />"
				+ word.substring(word.length-1, word.length)

			ids.push("answer_"+i)
			answers.push(word.substring(0, word.length - 1))
		}

		get("submit").value="Check"
		get("title").innerHTML  = title
		get("fillin").innerHTML = words.join("") + " (NKJV)"
		focus(ids[0])

		window.ids=ids
		window.answers=answers
		++window.position
	}
}

function scoreUpdate() {
	get("correct").innerHTML   = window.score_correct
	get("incorrect").innerHTML = window.score_incorrect
}

function check() {
	for (var i = 0; i < window.ids.length; ++i) {
		if (get(window.ids[i]).value.length == 0) {
			alert("Please fill in the blanks.")
			return false
		}
	}

	get("form").onsubmit=function() {
		nextQuestion()
		return false
	}

	get("submit").value="Next"
	focus("submit")
	
	var answer = document.createElement("span")
	answer.innerHTML = "<br /><br /><b>Answers</b><br />"
		+window.answers.join(", ")
		
	get("fillin").appendChild(answer)

	for (var i = 0; i < window.ids.length; ++i) {
		var e = get(window.ids[i])
		var a = window.answers[i].toLowerCase()
		
		e.disabled          = true
		e.style.background  = "#FFFFFF"
		e.style.textAlign   = "center"

		var theirs = e.value.toLowerCase().replace(/[^a-z]/g, "")

		if (theirs == a || (a.length > 4 && distance(theirs, a) < 3)) {
			e.style.color       = "#008800"
			e.style.borderColor = "#008800"

			++window.score_correct
		} else {
			e.style.color       = "#FF0000"
			e.style.borderColor = "#FF0000"

			++window.score_incorrect
		}
	}
	
	scoreUpdate()

}

function showDone() {
	window.needToConfirm = false

	get("end_correct").innerHTML = window.score_correct
	get("end_incorrect").innerHTML = window.score_incorrect

	get("select").style.display="none"
	get("question").style.display="none"
	get("done").style.display="inline"
}

window.onload=showSelect
// -->
</script>
<div class="title">Fill in the Blanks</div>
<div id="replace"></div>

<noscript><p>You need Javascript to use this part of the site.</p></noscript>

<div id="select">
<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="get" onsubmit="showQuiz(); return false">
<p>Please select the chapter you want to be quizzed over.</p>
<b>Quiz over</b> <select id="quiz_over"></select><br />
<b>Difficulty</b> <select id="difficulty"></select><br />
<input type="submit" value="Begin" onclick="showQuiz(); return false" />
</form>
<p><b>Note:</b> at most the first 1000 verses of the book(s) being studied will be displayed.</p>
</div>

<div id="question">
<form id="form" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="get" onsubmit="check(); return false">
<span class="bold" id="title"></span> - <span class="italic"><span class="green small">Correct: </span> <span class="green small" id="correct">0</span></span> <span class="small">|</span> <span class="italic"><span class="red small">Incorrect: </span> <span class="red small" id="incorrect">0</span></span><br />

<span id="fillin"></span><br />
<input type="submit" id="submit" value="Check" />
</form>
</div>

<div id="done">
<p>Congratulations! You have finished the quiz for <span class="bold" id="end_title"></span>.</p>
<p>Your score: <span class="green">Correct: </span> <span class="green" id="end_correct">0</span> | <span class="red">Incorrect: </span> <span class="red" id="end_incorrect">0</span></p>
<p><a href="javascript:void(0)" onclick="showSelect(); return false">Back</a></p>
</div>
<?php site_footer("New King James Version&reg;, Copyright &copy; 1982, Thomas Nelson, Inc. All rights reserved."); ?>
