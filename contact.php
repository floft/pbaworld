<?php
require_once "include.php";
require_once "include_email.php";

$antispam  = "cow";
$send_to   = site_email;
$send_from = site_email;
$subject   = "Message from " . site_name;
$error     = "";
$sent      = false;

function blank($name,$chars=false,$else="") {
	if (isset($_REQUEST[$name])) {
		if ($chars)
			return htmlspecialchars($_REQUEST[$name], ENT_QUOTES, 'UTF-8');
		else
			return $_REQUEST[$name];
	 } else {
		return $else;
	}
}

function clean($str) {
	$str = str_replace("\n", "", $str);
	$str = str_replace("\r", "", $str);
	return preg_replace("/[^A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-]/", "", $str);
}

if (isset($_REQUEST['antispam'])) {
	$JS		= !isset($_REQUEST['not_js']);
	$ANTI		= blank('antispam');
	$Email		= blank('email');
	$Referrer	= blank('refer');
	$Message	= blank('message');
	$Name		= clean(blank('name'));
	$Subject	= clean(blank('subject'));
	$IP		= $_SERVER['REMOTE_ADDR'];
	$UA		= $_SERVER['HTTP_USER_AGENT'];

	if ($ANTI != $antispam)
	{
		if ($JS)
		{
			echo "0";
			exit();
		}
		else
		{
			$error = "Please type in '$antispam' correctly.";
		}
	}
	else if (trim($Name) == "" || trim($Subject) == "" || trim($Message) == "")
	{
		if ($JS)
		{
			echo "0";
			exit();
		}
		else
		{
			$error = "Please fill in the form.";
		}
	}
	else if ($Email != "" && !valid_email($Email))
	{
		if ($JS)
		{
			$Email = "";
		}
		else
		{
			$error = "Invalid email address.";
		}
	}

	if ($error == "")
	{
		$From		= "$Name <$send_from>";
		$ReplyTo	= (strlen(trim($Email))>0)?"\nReply-To: $Name <$Email>":"";

		$email = <<<EOF
Name:	$Name
Email:	$Email
IP:	$IP
UA:	$UA
Refer:	$Referrer

$Message
EOF;
		mail($send_to, $Subject, $email, "From: $From$ReplyTo"); 

		$sent = true;

		if ($JS)
		{
			echo "1";
			exit();
		}
	}
}

site_header("Contact");
?>
<script type="text/javascript">
<!--
function send()
{
	if (document.form.name.value.replace(/\s/, "") != ""
	 && document.form.subject.value.replace(/\s/, "") != ""
	 && document.form.message.value.replace(/\s/, "") != "")
	{
		document.getElementById("invalid").style.display = 'none';

		var url="<?php echo $_SERVER["PHP_SELF"]; ?>"
			+"?antispam=<?php echo $antispam; ?>"
			+"&name="   +encodeURIComponent(document.form.name.value)
			+"&subject="+encodeURIComponent(document.form.subject.value)
			+"&email="  +encodeURIComponent(document.form.email.value)
			+"&message="+encodeURIComponent(document.form.message.value)
			+"&refer="  +encodeURIComponent(document.form.refer.value);
		var replace = document.getElementById("replace");
		var subject = document.form.subject.value.replace(/[\\n|\\r]/, ' ').replace('<', "&lt;").replace('>', "&gt;")
		var message = document.form.message.value.replace(/[\\n|\\r]/, '<br />').replace('<', "&lt;").replace('>', "&gt;")

		replace.innerHTML = 'Sending...';

		http(url, function(text) {
			if (text.replace(/\s/, "") == '1') {
				replace.innerHTML="Your email has been sent. Thank you.";
			} else {
				replace.innerHTML="An error has occured while sending your message. Please try again later. Here is a copy of your message:<br /><pre style='padding-left:15px'><b>"+subject+"</b><br />"+message+"</pre>";
			}
		})

		return true;
	} else {
		document.getElementById("invalid").style.display = 'inline';

		return false;
	}
}

function hidestuff() {
	document.getElementById('antispam').style.display='none';
}

window.onload=hidestuff
// -->
</script>
<?php
if ($sent == false)
{
?>
<div class="title">Contact</div>
<div id="replace">
<p>If you have any questions for us, you can ask them below. We are always happy to hear from you! If you want us to reply, remember to specify your email address.</p>

<span class="warning" id="invalid">Please fill in the form.</span>
<?php
if ($error != "")
	echo "<span class=\"warning\">$error</span>\n";
?>
<form method="post" onsubmit="return false" name="form" action="<?php echo $_SERVER["PHP_SELF"]; ?>">
<table>
<tr><td>Name</td>              <td><input type='text' value='<?php echo blank('name', true); ?>' name='name'    /></td></tr>
<tr><td>Subject</td>           <td><input type='text' value='<?php echo blank('subject', true); ?>' name='subject' /></td></tr>
<tr><td>Email (optional)</td>  <td><input type='text' value='<?php echo blank('email', true); ?>' name='email'   /></td></tr>
<tr id="antispam"><td>Type the word "<?php echo $antispam; ?>"</td> <td><input type='text' value='<?php echo blank('antispam', true); ?>' name='antispam' /></td></tr>
</table>

<p>
<input name='refer' type='hidden' value='<?php echo blank('refer', true, (isset($_SERVER['HTTP_REFERER']))?$_SERVER['HTTP_REFERER']:""); ?>' />
<textarea class="message" name='message' rows='15'><?php echo blank('message', true); ?></textarea><br />
<input type="submit" name="not_js" value="Submit" onclick="send(); return false;" />
</p>

</form>
</div>
<?php
}
else
{
	echo "<p>Your email has been sent. Thank you.</p>";
}

site_footer();
?>
