<?php
require_once "include.php";
site_header("Verify Email");

$hash = (isset($_REQUEST['h']))?$_REQUEST['h']:"";
?>
<script type="text/javascript">
<!--
window.onload=function() {
	var hash = "<?php echo addslashes($hash); ?>"
	replace.innerHTML = "Loading..."

	if (hash.length != 64) {
		get("replace").style.display = "none"
		get("invalid").style.display = "inline"
	} else {
		var url = "rpc_user.php?t=v"
			+"&h="+hash

		http(url, function(text) {
			var verified = JSON.parse(text)

			get("replace").style.display = "none"

			if (verified[0] == true) {
				get("done").style.display    = "inline"
			} else {
				get("invalid").style.display = "inline"
			}
		})
	}
}
// -->
</script>
<div class="title">Verify Email</div>
<div id="replace"></div>

<div id="invalid">
<p>Your email could not be verified. It is possible you already verified your email.</p>
</div>

<div id="done">
<p>Your email is now verified. You can now sign in on the <a href="user.php">login page</a>.</p>
</div>
<?php site_footer(); ?>
