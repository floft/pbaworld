<?php
/*
 * Usage:
 *  curl 'http://www.wopto.net/stuff/pba/rpc.php?y=1&t=q&b=Jeremiah&c=1' | \
 *      jshon -a -e 0 -u -p -e 1 -u                                      | \
 *      paste -s -d ":\n"
 *
 * rpc.php?t=lq					List book/chapters of questions
 * rpc.php?t=la					List archived book/chapters
 * rpc.php?t=lv					List book/chapters of Bible
 * rpc.php?t=lb					List books and abbreviations
 * rpc.php?t=q&b=Jeremiah&c=1			Get questions
 * rpc.php?t=v&b=Jeremiah&c=1			Get verses
 *
 * The 'c' arg can be a list such as '1,2,3-5,6-9'
*/

require_once "include.php";

function if_exists($name, $die=false) {
	if (isset($_REQUEST[$name]))
		return urldecode($_REQUEST[$name]);
	else
	{
		if ($die)	exit();
		else		return false;
	}
}

function rpc($type, $book="", $chapters="") {
	$year 		= pba_year;

	if (!preg_match("/^[0-9,-]*$/", $chapters))
		exit();

	$con    = dbopen();
	$return = array();
	$list   = array();

	// Make sure that if you're using this for verses, only display at max 1000
	if ($type == 'v' || $type == 'lv')
	{
		$book_list  = get_books();
		$book_max   = get_books_max($book_list, $con);
	}

	$items = explode(",", $chapters);

	//no reasonable query would have more than 100
	if (count($items) > 100)
		exit();

	foreach ($items as $item) {
		if ($item == "") {
			continue;
		} else if (strpos($item, "-") === false) {
			if ($type == 'v' && !valid_chapter($book_max, $book, $item))
				continue;

			$list[] = $item;
		} else {
			$range = explode("-", $item);

			if (count($range) != 2)
				exit();

			//no reasonable query would have more than 1500
			if (($range[1] - $range[0]) > 1500)
				continue;

			for ($i=$range[0]; $i<=$range[1]; ++$i)
			{
				if ($type == 'v' && !valid_chapter($book_max, $book, $i))
					continue;

				$list[] = $i;
			}
		}
	}

	if ($type == 'q') {
		$query = sprintf("select q.chapter, q.verse, q.question, q.answer from (select qid, max(id) as maxid from `questions` group by qid) as x inner join `questions` as q on q.qid = x.qid and q.id = x.maxid where book = '%s' and chapter in (%s) and q.deleted = 0 order by q.chapter, q.verse, q.id asc",
			mysql_real_escape_string($book),
			implode(",", $list));
		$result = mysql_query($query);
		$rows   = mysql_num_rows($result);

		for ($i=0; $i<$rows; ++$i)
		{
			$return[] = array(
				mysql_result($result, $i, 0),	//chapter
				mysql_result($result, $i, 1),	//verse
				mysql_result($result, $i, 2),	//question
				mysql_result($result, $i, 3),	//answer
			);
		}
	} else if ($type == 'v') {
		$query = sprintf("Select chapter,verse,text from `bible` where book = '%s' and chapter in (%s) order by chapter,verse asc",
			mysql_real_escape_string($book),
			implode(",", $list));
		$result = mysql_query($query);
		$rows   = mysql_num_rows($result);

		for ($i=0; $i<$rows; ++$i)
		{
			$return[] = array(
				mysql_result($result, $i, 0),	//chapter
				mysql_result($result, $i, 1),	//verse
				mysql_result($result, $i, 2),	//text
			);
		}
	} else if ($type == 'lq') {
		$query = sprintf("select distinct q.book, q.chapter from (select qid, max(id) as maxid from `questions` group by qid) as x inner join `questions` as q on q.qid = x.qid and q.id = x.maxid where q.year = '%s' and q.deleted = 0 order by q.book, q.chapter asc",
			mysql_real_escape_string($year));
		$result = mysql_query($query);
		$rows   = mysql_num_rows($result);

		for ($i=0; $i<$rows; ++$i)
		{
			$return[] = array(
				mysql_result($result, $i, 0),	//book
				mysql_result($result, $i, 1)	//chapter
			);
		}
	} else if ($type == 'la') {
		//only thing different is the "q.year != " to get everything except the current year
		$query = sprintf("select distinct q.book, q.chapter from (select qid, max(id) as maxid from `questions` group by qid) as x inner join `questions` as q on q.qid = x.qid and q.id = x.maxid where q.year != '%s' and q.deleted = 0 order by q.book, q.chapter asc",
			mysql_real_escape_string($year));
		$result = mysql_query($query);
		$rows   = mysql_num_rows($result);

		for ($i=0; $i<$rows; ++$i)
		{
			$return[] = array(
				mysql_result($result, $i, 0),	//book
				mysql_result($result, $i, 1)	//chapter
			);
		}
	} else if ($type == 'lv') {
		$return = $book_max;
	} else if ($type == 'lb') {
		$file = file("abbreviations.txt", FILE_SKIP_EMPTY_LINES | FILE_IGNORE_NEW_LINES);
		$len  = count($file);
		
		for ($i=0; $i<$len; ++$i) {
			$return[] = explode("\t", $file[$i]);
		}
	}

	dbclose($con);

	return $return;
}
?>
