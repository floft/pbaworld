<?php
function get_books() {
	$books = array();

	$parts = explode(",", pba_book);

	foreach ($parts as $part) {
		$pos   = strrpos($part, " ");
		$book  = trim(substr($part, 0, $pos));
		$chaps = trim(substr($part, $pos+1));

		$items = explode("-", $chaps);
		
		if (count($items) != 2)
			continue;

		$start    = $items[0];
		$end      = $items[1];
		$chapters = array();

		for ($i=$start; $i<=$end; ++$i)
			$chapters[] = $i;

		$books[$book] = $chapters;
	}

	return $books;
}

function get_books_max($books,$db=null) {
	if ($db == null)
		$con = dbopen();
	
	$total  = 0;
	$result = array();

	foreach ($books as $book => $chapters) {
		$query = sprintf("Select chapter,count(chapter) as count from `bible` where book = '%s' and chapter in (%s) group by chapter order by chapter",
			mysql_real_escape_string($book),
			implode(",", $chapters));
		
		$results = mysql_query($query);
		$count   = mysql_num_rows($results);

		for ($i=0; $i<$count; ++$i) {
			$chapter = mysql_result($results, $i, 0);
			$verses  = mysql_result($results, $i, 1);

			$total += $verses;

			if ($total <= max_verses) {
				$result[$book][] = $chapter;
			} else {
				break 2;
			}
		}
	}

	if ($db == null)
		dbclose($con);

	return $result;
}

function valid_chapter($books, $search_book, $search_chapter) {
	foreach ($books as $book => $chapters) {
		if ($book == $search_book) {
			foreach ($chapters as $chapter) {
				if ($chapter == $search_chapter)
					return true;
			}
		}
	}

	return false;
}

function valid_verse($book, $chapter, $verse, $db=null) {
	if ($db == null)
		$con = dbopen();

	$query = sprintf("Select count(*) from `bible` where book = '%s' and chapter = '%s' and verse = '%s'",
		mysql_real_escape_string($book),
		mysql_real_escape_string($chapter),
		mysql_real_escape_string($verse));
	
	$result = mysql_query($query);
	$count  = mysql_result($result, 0, 0);

	if ($db == null)
		dbclose($con);
	
	if ($count > 0)
		return true;
	else
		return false;
}
?>
