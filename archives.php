<?php
require_once "include.php";
site_header("Archives");
?>
<script type="text/javascript">
<!--
window.loaded_options = false

function loadOptions(func) {
	var url     = "rpc.php?t=la"
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
			
			get("select").style.display="inline"
		})
	}
	
	get("print").style.display="none"
}

function showPrint() {
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

window.onload=showSelect
// -->
</script>
<div class="title">Older Questions</div>
<div id="replace"></div>

<noscript><p>It appears that you don't have Javascript enabled. You will probably want to go to the <a href="html/archives.php">HTML version</a> of this page.</p></noscript>

<div id="select">
<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="get" onsubmit="showPrint(); return false;">
<p>These are the question archives from past years. Below are the links to some questions from several years and a form for the more recent years.<br />
<a href="http://pathfinders.floft.net/BibleAchivementMatthew.htm">Matthew</a><br />
<a href="http://www.floft.net/apps/quiz?id=a">2 Chronicles</a></p>

<p>
<b>Quiz over</b> <select id="print_questions"></select><br />
<b>Answers</b>   <select id="answer_location">
	<option value="0">with questions</option>
	<option value="1">at the end</option>
</select><br />
<input type="submit" value="Display" />
</p>
</form>
</div>

<div id="print"></div>
<?php site_footer(); ?>
