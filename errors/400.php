<?php
require_once "share.php";
list($uri,$encoded) = uri();
?>
<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">
<html>
<head>
<title>400 Bad Request</title>
</head>
<body>
<h1>Bad Request</h1>
<p>There was a problem loading <?php echo $uri; ?> on this server.</p>
<p><a href="http://floft.net/contact?subject=400-error&amp;name=Anonymous&amp;message=Error+on+<?php echo $encoded; ?>">Report Error</a></p>
</body>
</html>
