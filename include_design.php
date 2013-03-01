<?php
function menu() {
$menu = "";
$xml = simplexml_load_file(site_menu) or die("could not get menu: " . site_menu);

foreach ($xml->children() as $child)
{
	if ($child->getName() == "break")
	{
		$menu .= "<br />\n";
	}
	else
	{
		$url  = $child["url"];
		$self = basename($_SERVER["PHP_SELF"]);

		//is this the current page?
		if ($url == $self)
			$menu .= "<a href='$url' id='current'>$child</a>";
		else
			$menu .= "<a href='$url'>$child</a>";
		
		$menu .= "\n";
	}
}

return $menu;
}

function site_header($title) {
$name = site_name;
$menu = menu();

if (html_version == true)
	$name = "HTML ".$name;

if ($title == "Home")
	$title = $name;
else
	$title = "$title - $name";

echo <<<EOF
<!DOCTYPE html
	PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>$title</title>
<link rel="shortcut icon" href="favicon.ico" type="image/vnd.microsoft.icon" />
<link type="text/css" rel="stylesheet" media="all" href="/style.css" />
<script type="text/javascript" src="/json2.js"></script>
<script type="text/javascript" src="/script.js"></script>
<!--[if lte IE 7]>
<link type="text/css" rel="stylesheet" href="/style_IE7.css" media="all" />
<![endif]-->
<!--[if gte IE 8]>
<link type="text/css" rel="stylesheet" href="/style_IE.css" media="all" />
<![endif]-->
</head>
<body>
<div class="head"></div>
<div class="menu">
$menu</div>
<div class="content">

EOF;
}

function site_footer($text=null) {
	$year = date("y");

	if ($text != null)
		$text.="<br />";

	echo <<<EOF

</div>
<div class="tail"></div>
<div class="footer">$text
Website from the <a href="http://pathfinders.floft.net/" target="_blank">Cascade Eagles</a>, Copyright &copy; 07-$year Floft. Questions created by users.
</div>
</body>
</html>
EOF;
}
?>
