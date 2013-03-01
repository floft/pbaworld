<?php
function dbopen() {
	$con = mysql_connect(db_host, db_user, db_password);
	mysql_select_db(db_name, $con);

	return $con;
}

function dbclose($con) {
	mysql_close($con);
}
?>
