<?php
require_once "include_rpc.php";

$type     = if_exists('t', true);
$book     = if_exists('b');
$chapters = if_exists('c');

echo json_encode(rpc($type, $book, $chapters));
?>
