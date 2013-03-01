<?php
require_once "share.php";
list($uri,$encoded) = uri();
?>
<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">
<html>
<head>
<title>401 Authorization Required</title>
</head>
<body>
<h1>Authorization Required</h1>
<p>You don't have permission to access <?php echo $uri; ?> on this server.</p>
<p><a href="http://floft.net/contact?subject=401-error&amp;name=Anonymous&amp;message=Error+on+<?php echo $encoded; ?>">Report Error</a></p>
</body>
</html>
