<?php
require_once "include.php";
require_once "../include_rpc.php";
site_header("Print");

echo "<div class=\"title\">Print Questions</div>";

function intoWords($text) {
	$words     = array();
	$html      = false;
	$last_word = 0;
	$text_len  = strlen($text);

	for ($i=0; $i < $text_len; ++$i) {
		$character = substr($text, $i, 1);

		if ($html == true && $character != ">")
			continue;

		switch ($character) {
			case ".":
			case ",":
			case ";":
			case ":":
			case "?":
			case "!":
			case ")":
			case "]":
			case " ":
				$words[]   = substr($text, $last_word, $i - $last_word + 1);
				$last_word = $i + 1;
				break;
			case "<":
				if ($last_word != $i) {
					$words[]   = substr($text, $last_word, $i - $last_word);
					$last_word = $i;
				}

				$html = true;
				break;
			case ">":
				$words[]   = substr($text, $last_word, $i - $last_word + 1);
				$last_word = $i + 1;

				$html = false;
				break;
		}
	}

	return $words;
}

function goodWords($word) {
	$badWords = explode(",","a,an,are,and,am,as,at,be,by,co,can,but,because,since,did,for,has,have,from,etc,do,he,her,she,his,hers,i,is,if,it,in,no,ok,of,off,oh,how,on,or,so,to,too,the,then,that,this,will");
	$check    = preg_replace("/[^a-z]/", "", strtolower($word));
	$length   = count($badWords);

	for ($i=0; $i < $length; ++$i)
		if ($check == $badWords[$i])
			return false;

	return true;
}

if (isset($_REQUEST['q']) && isset($_REQUEST['a'])) {
	$parts = explode("-",urldecode($_REQUEST['q']));

	if (count($parts) == 2) {
		$book       = $parts[0];
		$chapter    = $parts[1];
		$commentary = ($book == "Commentary");
		$end        = ($_REQUEST['a'] == "1");

		if (isset($_REQUEST['d'])) {
			$list       = rpc("v", $book, $chapter);
			$list_len   = count($list);
			$difficulty = (is_numeric($_REQUEST['d']))?$_REQUEST['d']:10;

			$results = array();

			for ($q=0; $q < $list_len; ++$q) {
				$verse = $list[$q][1];
				$text  = $list[$q][2];

				$words     = intoWords($text);
				$words_len = count($words);

				$terms        = floor($difficulty / 100 * count($words));
				$words_left   = array();
				$replace_list = array();
				$answers      = array();

				for ($i=0; $i < $words_len; ++$i)
					if (preg_replace("/^\s+|\s+$/", "", $words[$i]) != ""
					&& goodWords($words[$i])
					&& preg_replace("/[^a-zA-Z]/", "", $words[$i]) != ""
					&& preg_match("/^[^<]/", $words[$i]))
						$words_left[] = $i;

				if (count($words_left) < $terms)
					$terms = count($words_left);
				else if ($terms < 1)
					$terms = 1;

				for ($i = 0; $i < $terms; ++$i) {
					$rand           = array_rand($words_left);
					$replace_list[] = $words_left[$rand];

					unset($words_left[$rand]);
				}

				sort($replace_list);
				$replace_len = count($replace_list);

				for ($i=0; $i < $replace_len; ++$i) {
					$word = $words[$replace_list[$i]];
					$words[$replace_list[$i]] = "________"
						. substr($word, -1, 1);

					$answers[] = substr($word, 0, -1);
				}

				$results[] = array($verse, implode("", $words), implode(", ", $answers));
			}

			$results_len = count($results);

			if ($end) {
				echo "<p><b>Questions for $book $chapter (NKJV)</b></p>";
				
				for ($i=0; $i < $results_len; ++$i) {
					$num      = $i+1;
					$verse    = $results[$i][0];
					$question = $results[$i][1];

					echo "<p><b>$num. Verse $verse</b><br />$question</p>";
				}

				echo "<p><b>Answers for $book $chapter</b></p>";
				
				for ($i=0; $i < $results_len; ++$i) {
					$num      = $i+1;
					$verse    = $results[$i][0];
					$answer   = $results[$i][2];

					echo "<p><b>$num. Verse $verse</b><br />$answer</p>";
				}
			} else {
				echo "<p><b>$book $chapter (NKJV)</b></p>";

				for ($i=0; $i < $results_len; ++$i) {
					$verse    = $results[$i][0];
					$question = $results[$i][1];
					$answer   = $results[$i][2];

					echo "<p><b>Verse $verse</b><br />"
					    ."<b>Q:</b> $question<br />"
					    ."<b>A:</b> $answer</p>";
				}
			}
		} else {
			$list = rpc("q", $book, $chapter);

			if ($end) {
				echo "<p><b>Questions for $book $chapter</b></p>";

				foreach ($list as $key=>$item) {
					$i        = $key+1;
					$verse    = $item[1];
					$question = $item[2];

					if ($commentary)
						echo "<p><b>$i.</b> $question</p>";
					else
						echo "<p><b>$i. Verse $verse</b><br />$question</p>";
				}

				echo "<p><b>Answers for $book $chapter</b></p>";

				foreach ($list as $key=>$item) {
					$i        = $key+1;
					$verse    = $item[1];
					$answer   = $item[3];

					if ($commentary)
						echo "<p><b>$i.</b> $answer</p>";
					else
						echo "<p><b>$i. Verse $verse</b><br />$answer</p>";
				}
			} else {
				echo "<p><b>$book $chapter</b></p>";
			
				foreach ($list as $item) {
					$verse    = $item[1];
					$question = $item[2];
					$answer   = $item[3];

					if ($commentary)
						echo "<p><b>Q:</b> $question<br />"
						    ."<b>A:</b> $answer</p>";
					else
						echo "<p><b>Verse $verse</b><br />"
						    ."<b>Q:</b> $question<br />"
						    ."<b>A:</b> $answer</p>";
				}
			}
		}
	}

	echo "<p><a href=\"${_SERVER['PHP_SELF']}\">Back</a></p>";
} else {
?>

<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="get">
<p>Please select the chapters you want and whether you want the questions to be displayed with the answers or at the end. Also, if you are printing the Fill in the Blanks questions, specify the percentage of the words that will be blank (or leave it at the default).</p>
<h3>Questions</h3>
<b>Quiz over</b> <select name="q"><?php
$list = rpc("lq");
$select = "";

foreach ($list as $item) {
	$book    = $item[0];
	$chapter = $item[1];
	echo "<option value=\"$book-$chapter\">$book $chapter</option>\n";
}
?></select><br />
<b>Answers</b>   <select name="a">
	<option value="0">with questions</option>
	<option value="1">at the end</option>
</select><br />
<input type="submit" value="Display" />
</form>
<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="get">
<h3>Fill in the Blanks</h3>
<b>Quiz over</b> <select name="q"><?php
$list = rpc("lv");
$select = "";

foreach ($list as $book=>$chapters) {
	foreach ($chapters as $chapter) {
		echo "<option value=\"$book-$chapter\">$book $chapter</option>\n";
	}
}
?></select><br />
<b>Difficulty</b> <select name="d"><?php
for ($i=5; $i<100; $i+=5) {
	if ($i == 10)
		echo "<option value=\"$i\" selected=\"selected\">$i%</option>\n";
	else
		echo "<option value=\"$i\">$i%</option>\n";
}
?></select><br />
<b>Answers</b>   <select name="a">
	<option value="0">with questions</option>
	<option value="1">at the end</option>
</select><br />
<input type="submit" value="Display" />
</form>

<p><b>Note:</b> at most the first 1000 verses of the book(s) being studied will be displayed.</p>
<?php
}

site_footer("New King James Version&reg;, Copyright &copy; 1982, Thomas Nelson, Inc. All rights reserved.");
?>
