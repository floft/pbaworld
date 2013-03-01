<?php
require_once "include.php";
site_header("Questions");
?>
<script type="text/javascript">
<!--
window.loaded_options = false

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

function loadOptions(func) {
	var list;
	var replace = get("replace")

	var url = "rpc.php?t=lq"

	replace.innerHTML = "Loading..."

	http(url, function(text) {
		var list = JSON.parse(text)

		replace.innerHTML = ""
		replace.style.display="none"
		func(list)
	})
}

function loadQuiz(book, chapters, random) {
	window.book            = book
	window.questions       = new Array()
	window.position        = 0
	window.score_correct   = 0
	window.score_incorrect = 0

	loadChapters(book, chapters, function(questionList) {
		window.questions=questionList;

		if (random) {
			window.questions.sort(function () {
				return 0.5 - Math.random()
			})
		}

		nextQuestion()
	})
}

function nextQuestion() {
	var i        = window.position
	var title    = window.book + " " + window.questions[i][0]
	var question = window.questions[i][2]
	
	if (window.book != "Commentary")
		title += ":" + window.questions[i][1]
	
	updateScore()
	showQuestion(title, question)
}

function nextAnswer() {
	var yours    = get("text").value

	if (yours.replace(/\s+/, "") == "") {
		alert("Please answer the question.")
		focus("text")
		return
	}

	var i        = window.position
	var title    = get("question_title").innerHTML
	var question = window.questions[i][2]
	var correct  = window.questions[i][3]

	showAnswer(title, question, correct, yours)
}

function nextQuiz() {
	window.needToConfirm = true

	var e       = get("quiz_over")
	var parts   = e.options[e.selectedIndex].value.split("-")
	var book    = parts[0]
	var chapter = parts[1]

	loadQuiz(book, chapter, true) //random is default?
}

function answered(correct) {
	if (correct) {
		++window.score_correct
	} else {
		++window.score_incorrect
	}

	++window.position

	if (window.questions.length == window.position) {
		showDone(window.score_correct, window.score_incorrect)
	} else {
		nextQuestion()
	}
}

function updateScore(correct, incorrect) {
	get("correct").innerHTML=window.score_correct
	get("incorrect").innerHTML=window.score_incorrect
	get("question_number").innerHTML=window.position+1
	get("total").innerHTML=window.questions.length
}

function showSelect() {
	if (window.loaded_options) {
		get("select").style.display="inline"
	} else {
		window.loaded_options = true

		loadOptions(function(list) {
			for(var i=0; i<list.length; ++i) {
				var book    = list[i][0]
				var chapter = list[i][1]

				var option = document.createElement("option")
				option.setAttribute("value", book + "-" + chapter)
				option.innerHTML = book + " " + chapter

				get("quiz_over").appendChild(option)
			}
		
			get("select").style.display="inline"
		})
	}
	
	get("done").style.display="none"
	get("answer").style.display="none"
	get("score").style.display="none"
	get("question").style.display="none"
}

function showQuestion(title, question) {
	get("text").value=""
	get("question_title").innerHTML=title
	get("question_text").innerHTML=question

	get("done").style.display="none"
	get("answer").style.display="none"
	get("select").style.display="none"
	get("score").style.display="inline"
	get("question").style.display="inline"

	focus("text")
}

function showAnswer(title, question, correct, yours) {
	get("answer_title").innerHTML=title
	get("answer_question").innerHTML=question
	get("answer_correct").innerHTML=correct
	get("answer_yours").innerHTML=yours

	get("question").style.display="none"
	get("done").style.display="none"
	get("select").style.display="none"
	get("score").style.display="inline"
	get("answer").style.display="inline"

	focus("correct_button")
}

function showDone(correct, incorrect) {
	window.needToConfirm = false

	get("total_correct").innerHTML=correct
	get("total_incorrect").innerHTML=incorrect

	get("score").style.display="none"
	get("question").style.display="none"
	get("answer").style.display="none"
	get("select").style.display="none"
	get("done").style.display="inline"
}

window.onload=showSelect
// -->
</script>
<div class="title">Questions</div>
<div id="replace"></div>

<noscript><p>You need Javascript to use this part of the site.</p></noscript>

<div id="select">
<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="get" onsubmit="nextQuiz(); return false;">
<b>Quiz over</b> <select id="quiz_over"></select> <input type="submit" value="Begin" />
</form>
</div>

<div id="score">
<span class="green">Correct: <span id="correct">0</span></span> |
<span class="red">Incorrect: <span id="incorrect">0</span></span>
<span class="blue right">
	Question <span id="question_number">0</span> of <span id="total">0</span>
</span>
<br /><br />
</div>

<div id="question">
<span class="bold" id="question_title"></span><br />
<span id="question_text"></span>
<br /><br />

<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="get" onsubmit="nextAnswer(); return false;">
<textarea rows="15" id="text"></textarea>
<input type="submit" value="Check" />
</form>
</div>

<div id="answer">
<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="get" onsubmit="alert('Please choose whether you answered correctly.'); return false;">
<span class="bold" id="answer_title"></span><br />
<blockquote><p id="answer_question"></p></blockquote>
<b>Answer:</b><br />
<blockquote><p id="answer_correct"></p></blockquote>
<b>Your Answer:</b><br />
<blockquote><p id="answer_yours"></p></blockquote>
<p>
Did you answer the question correctly?<br />
<input type="submit" value="Correct" onclick="answered(true); return false" id="correct_button" />
<input type="submit" value="Incorrect" onclick="answered(false); return false" />
</p>
</form>
</div>

<div id="done">
<p>Congratulations! You have finished the quiz!</p>

<p>You got
<span class="green"><span id="total_correct">0</span> correct</span>
and
<span class="red"><span id="total_incorrect">0</span> incorrect</span>
</p>

<a href="javascript:void(0)" onclick="showSelect(); return false">Back</a>
</div>
<?php site_footer(); ?>
