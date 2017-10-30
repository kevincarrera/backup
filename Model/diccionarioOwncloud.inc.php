<?php

$ADODB["adodb_path"]= "adodb5";
$ADODB["host"] = "192.168.1.94";

$ADODB["password"] = "555bad4c52";
$ADODB["user"] = "produser";

$ADODB["database_type"] = "mysqli";
include("{$ADODB["adodb_path"]}/adodb.inc.php");

$ADODB["database_name"] = "activacion";
$dbOwncloud = NewADOConnection($ADODB["database_type"]);
$dbOwncloud->Connect($ADODB["host"], $ADODB["user"], $ADODB["password"], $ADODB["database_name"]);

$dbOwncloud->Execute("SET GLOBAL group_concat_max_len=15360");

error_reporting(E_ALL);
ini_set("display_errors", 1);


