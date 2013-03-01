<?php
/*
 * user_rpc.php?t=l						Is the user logged in?
 * user_rpc.php?t=c&u=user&p=pass&e=email			Create account
 * user_rpc.php?t=o						Open a session and get code
 * user_rpc.php?t=o&u=user&p=pass				Open a session with code
 * user_rpc.php?t=x						Close a session
 * user_rpc.php?t=v&h=hash					Verify email
 * user_rpc.php?t=r&u=user					Reset password
 * user_rpc.php?t=r&h=hash&p=pass				Reset password
 * user_rpc.php?t=a&q=question&a=answer&l=location		Add    a question
 * user_rpc.php?t=m&i=qid&q=&a=&l="book chapter:verse"		Modify a question
 * user_rpc.php?t=d&i=qid					Delete a question
 * user_rpc.php?t=dd&i=qid					Permanently delete a question
 * user_rpc.php?t=list						List books
 * user_rpc.php?t=q&b=book&c=1					Get questions
 * user_rpc.php?t=i&i=qid					Get question revisions
 * user_rpc.php?t=qr						Get 200 most recent questions
 * user_rpc.php?t=qd						Get deleted questions
 * user_rpc.php?t=qy						Get users's questions
 * user_rpc.php?t=u						Get user information
 * user_rpc.php?t=uu&u=user&e=email&n=new&p=old_pass		Set user information
*/

//stay logged in for 2 weeks
$minutes = 20160;
session_set_cookie_params($minutes*60);
session_cache_expire($minutes);
ini_set("session.gc_maxlifetime", $minutes*60); 
session_start();

require_once "include.php";
require_once "include_email.php";

$year		= pba_year;
$type           = if_exists('t', true);
$user           = if_exists('u');
$pass           = if_exists('p');
$email          = if_exists('e');
$qid            = if_exists('i');
$question       = if_exists('q');
$answer         = if_exists('a');
$book           = if_exists('b');
$chapter        = if_exists('c');
$verse          = if_exists('v');
$hash		= if_exists('h');
$location	= if_exists('l');
$new_password   = if_exists('n');
$user_id	= (isset($_SESSION[site_session]))?$_SESSION[site_session]:"";

//sha256 are 64 characters long
function validSha($str) {
	if (strlen($str) != 64)
		return false;
	else
		return true;
}

function bad_words($text) {
	$words = file("badwords.txt", FILE_SKIP_EMPTY_LINES | FILE_IGNORE_NEW_LINES);
	$text  = strtolower($text);

	foreach ($words as $word) {
		if (strpos($text, "$word ") !== false)
			return true;
		if (strpos($text, " $word") !== false)
			return true;
	}

	return false;
}

function valid_question($question) {
	if (!preg_match(
		"/^[A-Za-z0-9`~!@# \$%^&*()_\-+={}\[\]|\\:;\"',\.\?\/]+$/",
		$question))
		return false;
	
	if (bad_words($question))
		return false;
	
	$question = trim($question);
	$len      = strlen($question);

	if ($len < 3 || $len > 10000)
		return false;

	return true;
}

function valid_username($user) {
	if (strlen($user) < 4 || strlen($user) > 30)
		return false;
	
	if (!preg_match("/^[A-Za-z0-9\-_\.]+$/", $user))
		return false;
	
	$query = sprintf("select count(id) from `account` where name = '%s'",
		mysql_real_escape_string($user));
	$result = mysql_query($query);
	$count  = mysql_result($result, 0, 0);

	if ($count > 0)
		return false;
	
	return true;
}

function loggedIn() {
	return isset($_SESSION[site_session]);
}

function if_exists($name, $die=false) {
	if (isset($_REQUEST[$name]))
		return urldecode($_REQUEST[$name]);
	else
	{
		if ($die)	exit();
		else		return false;
	}
}

function encrypt($key, $text) {
	$result = array();
	$length = strlen($text);

	for ($i=0; $i<$length; ++$i) {
		$result[] = $key^ord($text[$i]);
	}

	return json_encode($result);
}

function decrypt($key, $text) {
	$result  = "";
	$encoded = json_decode($text);

	if ($encoded !== false) {
		$length  = count($encoded);

		for ($i=0; $i<$length; ++$i) {
			$result .= chr($key^$encoded[$i]);
		}

		return $result;
	} else {
		return false;
	}
}

function user_hash($user) {
	return hash("sha256", site_seed . site_name . $user . time() . rand());
}

function user_group($id) {
	$query = sprintf("select `group` from `account` where id = '%s' limit 1",
		mysql_real_escape_string($id));
	$result = mysql_query($query);
	$count  = mysql_num_rows($result);

	if ($count > 0)
		return mysql_result($result, 0, 0);
	else
		return false;

}

function getChapters($chapters) {
	$list = array();

	if (!preg_match("/^[0-9,-]*$/", $chapters))
		return false;

	$items = explode(",", $chapters);

	foreach ($items as $item) {
		if ($item == "") {
			continue;
		} else if (strpos($item, "-") === false) {
			$list[] = $item;
		} else {
			$range = explode("-", $item);

			if (count($range) != 2)
				return false;

			for ($i=$range[0]; $i<=$range[1]; ++$i)
				$list[] = $i;
		}
	}

	return $list;
}

$con    = dbopen();
$return = array();

if ($type == 'l') {
	$loggedin = loggedIn();
	$group    = -1;

	if ($loggedin)
		$group = user_group($user_id);

	$return = array($loggedin, $group);
} else if ($type == 'c') {
	$created    = false;
	$user_okay  = false;
	$email_okay = false;

	if ($user != "" && $email != "" && $pass != "") {
		if (valid_email($email))
			$email_okay = true;

		if (valid_username($user))
			$user_okay = true;
	}

	if ($user_okay == true && $email_okay == true) {
		$hash = user_hash($user);

		$query = sprintf("insert into `account` (`group`, name, email, password, hash)
			values ('%s', '%s', '%s', '%s', '%s')",
			-1,	//require them to validate email
			mysql_real_escape_string($user),
			mysql_real_escape_string($email),
			mysql_real_escape_string($pass),
			mysql_real_escape_string($hash));
		$result   = mysql_query($query);
		$affected = mysql_affected_rows();

		if ($affected > 0) {
			$created = true;
			$url = "http://".site_url."/verify.php?h=".$hash;
			$site = site_name;
			$subject = "Verify Email";
			$message = <<<EOF
Greetings $user,

Somebody has created an account on our website using this email address. If
this wasn't you, please ignore this message. If this was you, please click
the URL below to verify your email.

$url

$site
EOF;
				mail($email, $subject, $message, "From: ".site_name." <".site_email.">");
		}
	}
	
	$return = array($user_okay, $email_okay, $created);
} else if ($type == 'o') {
	if ($user == "" && $pass == "") {
		$key = rand() + site_seed;

		$_SESSION[site_session."_key"] = $key;
		$return                        = $key;
	} else {
		$key = $_SESSION[site_session."_key"];
		unset($_SESSION[site_session."_key"]);

		$pass = decrypt($key, $pass);
		
		if (!validSha($pass)) {
			$return = array(false);
		} else {
			$query = sprintf("select id,`group` from `account` where name = '%s' and password = '%s' limit 1",
				mysql_real_escape_string($user),
				mysql_real_escape_string($pass));
			$result = mysql_query($query);
			$count  = mysql_num_rows($result);

			if ($count == 1) {
				$id    = mysql_result($result, 0, 0);
				$group = mysql_result($result, 0, 1);

				if ($group >= 0) {
					$_SESSION[site_session] = $id;
					$return = array(true);
				} else {
					$return = array(false);
				}
			} else {
				$return = array(false);
			}
		}
	}

} else if ($type == 'r') {
	//send validation email
	if ($user != "") {
		$query = sprintf("select email from `account` where name = '%s' limit 1",
			mysql_real_escape_string($user));
		$result = mysql_query($query);
		$count  = mysql_num_rows($result);

		if ($count > 0) {
			$return = array(true);
			$email  = $user . " <".mysql_result($result, 0, "email").">";
			$hash   = user_hash($user);
			
			$query = sprintf("update `account` set hash = '%s', new_email = '' where name = '%s' and `group` >= 0 limit 1",
				mysql_real_escape_string($hash),
				mysql_real_escape_string($user));
			$result = mysql_query($query);
			$affected = mysql_affected_rows();
			
			if ($affected > 0) {
				$url = "http://".site_url."/reset.php?h=".$hash;
				$site = site_name;
				$subject = "Reset Password";
				$message = <<<EOF
Greetings $user,

Recently somebody has attempted to reset your pasword for your account on our
website. If this wasn't you, please ignore this message. If this was you, go
to the following link to reset your password.

$url

$site
EOF;
				mail($email, $subject, $message, "From: ".site_name." <".site_email.">");
			}
		} else {
			$return = array(false);
		}
	
	//change password after clicking emailed link
	} else {
		$valid = false;

		if ($hash != "" && $pass != "" && validSha($pass) && validSha($hash)) {
			$query = sprintf("update `account` set hash = '', password = '%s' where hash = '%s' limit 1",
				mysql_real_escape_string($pass),
				mysql_real_escape_string($hash));
			$result = mysql_query($query);
			$affected = mysql_affected_rows();
			
			if ($affected > 0)
				$valid = true;
		}

		$return = array($valid);
	}
} else if ($type == 'v') {
	$valid = false;

	if ($hash != "" && validSha($hash)) {
		$query = sprintf("select email, new_email from `account` where hash = '%s' limit 1",
			mysql_real_escape_string($hash));
		$result = mysql_query($query);
		$count  = mysql_num_rows($result);

		if ($count > 0) {
			$email     = mysql_result($result, 0, 0);
			$new_email = mysql_result($result, 0, 1);

			if ($new_email != "")
				$email = $new_email;
			
			$query = sprintf("update `account` set hash = '', `group` = '%s',
				email = '%s', new_email = '' where hash = '%s' limit 1",
				0,
				mysql_real_escape_string($email),
				mysql_real_escape_string($hash));
			$result = mysql_query($query);
			$affected = mysql_affected_rows();
			
			if ($affected > 0)
				$valid = true;
		}
	}

	$return = array($valid);
} else if ($type == 'x') {
	unset($_SESSION[site_session]);
} else if ($type == 'list') {
	$return = get_books();
} else if ($type == 'm' || $type == 'a') {
	if (!loggedIn())
		exit();

	$commentary     = false;
	$valid_loc      = false;
	$valid_question = false;
	$valid_answer   = false;
	$added          = false;

	if (preg_match("/^([a-zA-Z\ 0-9]+)\ ([0-9]+):([0-9]+)$/", $location, $matches)) {
		$book       = $matches[1];
		$chapter    = $matches[2];
		$verse      = $matches[3];
		$valid_loc  = valid_verse($book, $chapter, $verse, $con);
	} else if (preg_match("/^Page\ ([0-9]+)$/", $location, $matches)) {
		$valid_loc  = true;
		$commentary = true;
		$book       = "Commentary";
		$chapter    = $matches[1];
		$verse      = 0;
	}

	$valid_question = valid_question($question);
	$valid_answer   = valid_question($answer);
	
	if ($valid_loc != false && $valid_question != false && $valid_answer != false) {
		$continue = false;

		if ($type == 'm') {
			if (is_numeric($qid)) {
				$query = sprintf("select book,chapter,verse,question,answer from `questions`
					where qid = '%s' and year = '%s' order by id desc limit 1",
					mysql_real_escape_string($qid),
					mysql_real_escape_string($year));
				$result = mysql_query($query);
				$count  = mysql_num_rows($result);

				if ($count > 0) {
					$previous_book     = mysql_result($result, 0, 0);
					$previous_chapter  = mysql_result($result, 0, 1);
					$previous_verse    = mysql_result($result, 0, 2);
					$previous_question = mysql_result($result, 0, 3);
					$previous_answer   = mysql_result($result, 0, 4);

					if ($book != $previous_book ||
						$chapter  != $previous_chapter ||
						$verse    != $previous_verse   ||
						$question != $previous_question ||
						$answer   != $previous_answer)
						$continue = true;
				}
			}
		} else {
			$query  = "select max(qid) from `questions`";
			$result = mysql_query($query);
			$qid    = mysql_result($result, 0, 0) + 1;

			if ($qid > 1)
				$continue = true;
		}

		if ($continue) {
			$query = sprintf("insert into `questions` (qid,year,book,chapter,verse,question,answer,date,name)
				values ('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')",
				mysql_real_escape_string($qid),
				mysql_real_escape_string($year),
				mysql_real_escape_string($book),
				mysql_real_escape_string($chapter),
				mysql_real_escape_string($verse),
				mysql_real_escape_string($question),
				mysql_real_escape_string($answer),
				time(),
				mysql_real_escape_string($user_id));
			$result = mysql_query($query);
			$affected = mysql_affected_rows();

			if ($affected > 0)
				$added = $qid;
		}
		
	}
	
	if ($added === false)
		$return = array($valid_loc, $valid_question, $valid_answer, $added);
	else
		$return = array($valid_loc, $valid_question, $valid_answer, $added, $book, $chapter, $verse);
} else if ($type == 'd') {
	if (!loggedIn())
		exit();

	$deleted = false;

	$query = sprintf("select count(id) from `questions` where qid = '%s' and year = '%s' limit 1",
		mysql_real_escape_string($qid),
		mysql_real_escape_string($year));
	$result = mysql_query($query);
	$count  = mysql_result($result, 0, 0);

	if ($count > 0) {
		$query = sprintf("insert into `questions` (qid,year,date,name,deleted)
			values ('%s', '%s', '%s', '%s', '%s')",
			mysql_real_escape_string($qid),
			mysql_real_escape_string($year),
			time(),
			mysql_real_escape_string($user_id),
			1);
		$result   = mysql_query($query);
		$affected = mysql_affected_rows();

		if ($affected > 0)
			$deleted = true;
	}

	$return = array($deleted);
} else if ($type == 'dd') {
	if (!loggedIn() || user_group($user_id) < 1)
		exit();

	$deleted = false;

	$query = sprintf("delete from `questions` where qid = '%s' and year = '%s'",
		mysql_real_escape_string($qid),
		mysql_real_escape_string($year));
	$result = mysql_query($query);
	$affected = mysql_affected_rows();

	if ($affected > 0)
		$deleted = true;
	
	$return = array($deleted);
} else if ($type == 'q') {
	if ($list = getChapters($chapter)) {
		$query = sprintf("select q.qid, q.book, q.chapter, q.verse, q.question, q.answer from (select qid, max(id) as maxid from `questions` group by qid) as x inner join `questions` as q on q.qid = x.qid and q.id = x.maxid where book = '%s' and chapter in (%s) and q.deleted = 0 and year = '%s' order by q.chapter, q.verse, q.id asc",
			mysql_real_escape_string($book),
			implode(",", $list),
			mysql_real_escape_string($year));
		$result = mysql_query($query);
		$rows   = mysql_num_rows($result);

		for ($i=0; $i<$rows; ++$i)
		{
			$return[] = array(
				mysql_result($result, $i, 0),	//qid
				mysql_result($result, $i, 1),	//book
				mysql_result($result, $i, 2),	//chapter
				mysql_result($result, $i, 3),	//verse
				mysql_result($result, $i, 4),	//question
				mysql_result($result, $i, 5),	//answer
			);
		}
	} else if ($book == "Commentary") {
		$query = sprintf("select q.qid, q.chapter, q.question, q.answer from (select qid, max(id) as maxid from `questions` group by qid) as x inner join `questions` as q on q.qid = x.qid and q.id = x.maxid where book = '%s' and q.deleted = 0 and year = '%s' order by q.chapter, q.verse, q.id asc",
			mysql_real_escape_string($book),
			mysql_real_escape_string($year));
		$result = mysql_query($query);
		$rows   = mysql_num_rows($result);

		for ($i=0; $i<$rows; ++$i)
		{
			$return[] = array(
				mysql_result($result, $i, 0),	//qid
				mysql_result($result, $i, 1),	//page
				mysql_result($result, $i, 2),	//question
				mysql_result($result, $i, 3),	//answer
			);
		}
	}
} else if ($type == 'qr') {
	$query = sprintf("select q.qid, q.book, q.chapter, q.verse, q.question, q.answer from (select qid, max(id) as maxid from `questions` group by qid) as x inner join `questions` as q on q.qid = x.qid and q.id = x.maxid where year = '%s' and q.deleted = 0 order by q.date desc, q.book, q.chapter, q.verse, q.id limit 200",
		mysql_real_escape_string($year));
	$result = mysql_query($query);
	$rows   = mysql_num_rows($result);

	for ($i=0; $i<$rows; ++$i)
	{
		$return[] = array(
			mysql_result($result, $i, 0),	//qid
			mysql_result($result, $i, 1),	//book
			mysql_result($result, $i, 2),	//chapter
			mysql_result($result, $i, 3),	//verse
			mysql_result($result, $i, 4),	//question
			mysql_result($result, $i, 5),	//answer
		);
	}
} else if ($type == 'qd') {
	if (!loggedIn() || user_group($user_id) < 1)
		exit();

	$query = sprintf("select f.qid, f.book, f.chapter, f.verse, f.question, f.answer from (
			select d.qid, max(d.id) as maxid from (
				select b.qid, a.maxid as maxid from(
					select id, max(id) as maxid
					from `questions`
					where year = '%s'
					group by qid
				) as a inner join `questions` as b
				on a.maxid = b.id
				where b.deleted = 1
			) as c inner join `questions` as d
			on c.qid = d.qid and c.maxid > d.id
			group by d.qid
		) as e inner join `questions` as f
		on e.qid = f.qid and e.maxid = f.id",
		mysql_real_escape_string($year));
	$result = mysql_query($query);
	$rows   = mysql_num_rows($result);

	for ($i=0; $i<$rows; ++$i)
	{
		$return[] = array(
			mysql_result($result, $i, 0),	//qid
			mysql_result($result, $i, 1),	//book
			mysql_result($result, $i, 2),	//chapter
			mysql_result($result, $i, 3),	//verse
			mysql_result($result, $i, 4),	//question
			mysql_result($result, $i, 5),	//answer
		);
	}
} else if ($type == 'qy') {
	if (!loggedIn())
		exit();

	$query = sprintf("select q.qid, q.book, q.chapter, q.verse, q.question, q.answer from (select qid, max(id) as maxid from `questions` group by qid) as x inner join `questions` as q on q.qid = x.qid and q.id = x.maxid where name = '%s' and year = '%s' and q.deleted = 0 order by q.book asc, q.chapter, q.verse, q.id",
		mysql_real_escape_string($user_id),
		mysql_real_escape_string($year));
	$result = mysql_query($query);
	$rows   = mysql_num_rows($result);

	for ($i=0; $i<$rows; ++$i)
	{
		$return[] = array(
			mysql_result($result, $i, 0),	//qid
			mysql_result($result, $i, 1),	//book
			mysql_result($result, $i, 2),	//chapter
			mysql_result($result, $i, 3),	//verse
			mysql_result($result, $i, 4),	//question
			mysql_result($result, $i, 5),	//answer
		);
	}
} else if ($type == 'u') {
	if (!loggedIn())
		exit();

	$query = sprintf("select name,email from `account` where id = '%s' limit 1",
		mysql_real_escape_string($user_id));
	$result = mysql_query($query);
	$rows   = mysql_num_rows($result);

	if ($rows > 0) {
		$return = array(
			mysql_result($result, 0, 0),	//name
			mysql_result($result, 0, 1),	//email
		);
	}
} else if ($type == 'uu') {
	if (!loggedIn())
		exit();

	$saved          = false;
	$emailed        = false;

	$user_okay      = false;
	$email_okay     = false;
	$valid_password = false;

	if (valid_email($email))
		$email_okay = true;
	
	if (validSha($pass)) {
		$query = sprintf("select name,email,password from `account` where id = '%s' and password = '%s'",
			mysql_real_escape_string($user_id),
			mysql_real_escape_string($pass));
		$result = mysql_query($query);
		$count  = mysql_num_rows($result);

		if ($count > 0) {
			$old_name  = mysql_result($result, 0, 0);
			$old_email = mysql_result($result, 0, 1);
			$old_pass  = mysql_result($result, 0, 2);
			$valid_password = true;
		}
	}

	if ($old_name == $user || valid_username($user))
		$user_okay = true;
	
	if ($user_okay == true && $email_okay == true && $valid_password == true) {
		//only update if changed
		$save_password = (($new_password=="")?$old_pass:$new_password);
		
		if ($email != $old_email) {
			$hash  = user_hash($user);
			$query = sprintf("update `account` set name = '%s', new_email ='%s', password = '%s', hash = '%s'
				where id = '%s' and password = '%s' limit 1",
				mysql_real_escape_string($user),
				mysql_real_escape_string($email),
				mysql_real_escape_string($save_password),
				mysql_real_escape_string($hash),
				mysql_real_escape_string($user_id),
				mysql_real_escape_string($pass));
			$result = mysql_query($query);
			$affected = mysql_affected_rows();
			
			if ($affected > 0) {
				$saved = true;
				$emailed = true;
				$url = "http://".site_url."/verify.php?h=".$hash;
				$site = site_name;
				$subject = "Verify Email";
				$message = <<<EOF
Greetings $user,

Somebody on our site has set this as their email. If this wasn't you, please
ignore this message. If this was you, please click the URL below to finish
changing your email.

$url

$site
EOF;
				mail($email, $subject, $message, "From: ".site_name." <".site_email.">");
			}
		} else {
			$query = sprintf("update `account` set name = '%s', password = '%s'
				where id = '%s' and password = '%s' limit 1",
				mysql_real_escape_string($user),
				mysql_real_escape_string($save_password),
				mysql_real_escape_string($user_id),
				mysql_real_escape_string($pass));
			$result = mysql_query($query);
			$affected = mysql_affected_rows();
			
			if ($affected > 0)
				$saved = true;
		}
	}
	
	$return = array($user_okay, $email_okay, $valid_password, $saved, $emailed);
} else if ($type == 'i') {
	$query = sprintf("select a.book, a.chapter, a.verse, b.name,
		from_unixtime(a.date, '%%m/%%d/%%y'), a.question, a.answer, a.deleted from (
			select name, book, chapter, verse, date, question, answer, deleted
			from `questions` where qid = '%s' order by id desc
		) as a inner join `account` as b
		on a.name = b.id",
		mysql_real_escape_string($qid));
	$result = mysql_query($query);
	$count  = mysql_num_rows($result);

	if ($count > 0) {
		for ($i = 0; $i < $count; ++$i) {
			$return[] = array(
				mysql_result($result, $i, 0), //book
				mysql_result($result, $i, 1), //chapter
				mysql_result($result, $i, 2), //verse
				mysql_result($result, $i, 3), //username
				mysql_result($result, $i, 4), //date
				mysql_result($result, $i, 5), //question
				mysql_result($result, $i, 6), //answer
				mysql_result($result, $i, 7) //deleted
			);
		}
	}
}
 
echo json_encode($return);

dbclose($con);
?>
