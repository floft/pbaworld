<?php
require_once "include.php";
site_header("Reset Password");

$hash = (isset($_REQUEST['h']))?$_REQUEST['h']:"";
?>
<script type="text/javascript" src="sha256.js"></script>
<script type="text/javascript">
<!--
function resetPassword() {
	var hash      = get("hash").value
	var password  = Sha256.hash(get("password").value)
	var password2 = Sha256.hash(get("password2").value)
	
	get("invalid").style.display="none"
	get("invalid_passwords").style.display="none"

	if (password == password2 && get("password").value != "") {
		get("password").value  = ""
		get("password2").value = ""
	} else {
		get("invalid_passwords").style.display="inline"
		focus("password")
		return
	}
	
	var url = "rpc_user.php?t=r"
		+"&h="+hash
		+"&p="+password

	http(url, function(text) {
		var reset = JSON.parse(text)

		if (reset[0] == true) {
			get("reset").style.display   = "none"
			get("done").style.display    = "inline"
		} else {
			get("invalid").style.display = "inline"
		}
	})
}

window.onload=function() {
	if (get("hash").value.length != 64) {
		get("reset").style.display = "none"
		get("replace").innerHTML = get("invalid").innerHTML
	} else {
		focus("password")
	}
}
// -->
</script>
<div class="title">Reset Password</div>
<div id="replace"></div>
<div id="reset">
<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" onsubmit="resetPassword(); return false;">
<input type="hidden" id="hash" value="<?php echo $hash; ?>" />
<span class="warning" id="invalid">Your password could not be reset. It is possible you already used this one-time URL to reset your password. Reset your password again to get a new URL.</span>
<span class="warning" id="invalid_passwords">Passwords don't match.</span>
<table>
<tr>
<td>New Password&nbsp;</td>
<td><input type="password" id="password" /></td>
</tr>
<tr>
<td><i>(again)</i></td>
<td><input type="password" id="password2" /></td>
</tr>
<tr>
<td colspan="2">
<input type="submit" value="Reset" /></td>
</td>
</tr>
</table>
</form>
</div>

<div id="done">
<p>Your password has been reset. Please return to the <a href="user.php">login page</a>.</p>
</div>
<?php site_footer(); ?>
