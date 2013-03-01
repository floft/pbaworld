<?php
function uri() {
	$request_uri	= (isset($_SERVER["REQUEST_URI"]))?$_SERVER["REQUEST_URI"]:"";
	$referer	= (isset($_SERVER["HTTP_REFERER"]))?$_SERVER["HTTP_REFERER"]:"";
	$php_self	= (isset($_SERVER["PHP_SELF"]))?$_SERVER["PHP_SELF"]:"";

	$uri = ($request_uri == $php_self)?$referer:$request_uri;
	$display = htmlspecialchars($uri);
	$encoded = (substr($uri,0,1) == "/")?"/".urlencode(substr($uri,1)):$uri;

	return array($display,$encoded);
}
?>
