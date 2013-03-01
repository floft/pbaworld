<?php
require_once "share.php";
list($uri,$encoded) = uri();
?>
<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">
<html>
<head>
<title>404 Page Not Found</title>
</head>
<body>
<h1>Page Not Found</h1>
<p>The URL <?php echo $uri; ?> was not found on this server.</p>
<?php if (!empty($uri)) { ?>
<p><a href="http://floft.net/contact?subject=404-error&amp;name=Anonymous&amp;message=Error+on+<?php echo $encoded; ?>">Report Error</a><br /><a href="http://floft.net/search404?url=<?php echo $encoded; ?>">Search Floft.net</a></p>
<?php } ?>
</body>
</html>
