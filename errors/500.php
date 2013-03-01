<?php
require_once "share.php";
list($uri,$encoded) = uri();
?>
<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">
<html>
<head>
<title>500 Internal Server Error</title>
</head>
<body>
<h1>Internal Server Error</h1>
<p>There was a server configuration error. Could not load <?php echo $uri; ?> on this server.</p>
<p><a href="http://floft.net/contact?subject=500-error&amp;name=Anonymous&amp;message=Error+on+<?php echo $encoded; ?>">Report Error</a></p>
</body>
</html>
