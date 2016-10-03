<?php

$bitcointalk=array();

$data=NULL;


/*
Don't change the following things.
Following settings are basic settings to connect to BitcoinTalk.

*/
$bitcointalk["loginUrl"]="https://bitcointalk.org/index.php?action=login2";
$bitcointalk["cookieLength"]="-1";
$bitcointalk["cookieFile"]= __DIR__ . "0.cookie";
$bitcointalk["UserAgent"]="Mozilla/5.0 (Windows; U; Windows NT 5.0; en-US; rv:1.7.12) Gecko/20050915 Firefox/1.0.7";
$bitcointalk["afterLoginHeaders"]=array();
$bitcointalk["isLoggedIn"]=false;

?>
