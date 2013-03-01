<?php
require_once "include.php";
require_once "../include_rpc.php";
site_header("Archives");

echo "<div class=\"title\">Older Questions</div>";

if (isset($_REQUEST['q']) && isset($_REQUEST['a'])) {
	$parts = explode("-",urldecode($_REQUEST['q']));
	
	if (count($parts) == 2) {
		$book       = $parts[0];
		$chapter    = $parts[1];
		$list       = rpc("q", $book, $chapter);
		$commentary = ($book == "Commentary");
		$end        = ($_REQUEST['a'] == "1");

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
		
		echo "<p><a href=\"${_SERVER['PHP_SELF']}\">Back</a></p>";
	}
} else {
?>
<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="get">
<p>These are the question archives from past years. Below are the links to some questions from several years and a form for the more recent years.<br />
<a href="http://pathfinders.floft.net/BibleAchivementMatthew.htm">Matthew</a><br />
<a href="http://www.floft.net/apps/quiz?id=a">2 Chronicles</a></p>

<p>
<b>Quiz over</b> <select name="q"><?php
	$list = rpc("la");
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
</p>
</form>
<?php
}

site_footer();
?>
