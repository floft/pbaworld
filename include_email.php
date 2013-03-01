<?php
// http://www.linuxjournal.com/article/9585?page=0,3
function valid_email($email)
{
	$atIndex = strrpos($email, "@");

	if ($atIndex === false)
		return false;

	$domain    = substr($email, $atIndex+1);
	$local     = substr($email, 0, $atIndex);
	$localLen  = strlen($local);
	$domainLen = strlen($domain);

	if ($localLen < 1 || $localLen > 64)
	{
		// local part length exceeded
		return false;
	}
	if ($domainLen < 1 || $domainLen > 255)
	{
		// domain part length exceeded
		return false;
	}
	if ($local[0] == '.' || $local[$localLen-1] == '.')
	{
		// local part starts or ends with '.'
		return false;
	}
	if (preg_match('/\\.\\./', $local))
	{
		// local part has two consecutive dots
		return false;
	}
	if (!preg_match('/^[A-Za-z0-9\\-\\.]+$/', $domain))
	{
		// character not valid in domain part
		return false;
	}
	if (preg_match('/\\.\\./', $domain))
	{
		// domain part has two consecutive dots
		return false;
	}
	if (!preg_match('/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/',
		str_replace("\\\\","",$local)))
	{
		// character not valid in local part unless 
		// local part is quoted
		if (!preg_match('/^"(\\\\"|[^"])+"$/',
			str_replace("\\\\","",$local)))
		{
			return false;
		}
	}

	/*if (!(checkdnsrr($domain,"MX") || 
		checkdnsrr($domain,"A")))
	{
		// domain not found in DNS
		return false;
	}*/

	return true;
}
?>
