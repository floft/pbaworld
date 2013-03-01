<?php
define("site_name",     "PBA World");
define("site_url",      "example.com");
define("site_session",  "session_name");
define("site_email",    "user@domain.tld");
define("site_menu",     "menu.xml");
define("site_seed",     1234567890); //random number

define("pba_year",      4);
#define("pba_book",     "Mark 1-16, Acts 1-28, 1 Thessalonians 1-5, 2 Thessalonians 1-3");
define("pba_book",      "Mark 1-16");

define("max_verses",    1000);

define("db_host",       "localhost");
define("db_user",       "username");
define("db_name",       "dbname");
define("db_password",   "password");

if (!defined("html_version"))
    define("html_version", false);

require_once "include_design.php";
require_once "include_account.php";
require_once "include_books.php";
?>
