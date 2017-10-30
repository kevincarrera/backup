<?php
$ADODB["adodb_path"]= "adodb5";
$ADODB["host"] = "192.168.1.20";

$ADODB["password"] = "as45591750e";
$ADODB["user"] = "phpuser";


$ADODB["database_name"] = "calidad";


/* comentario lalalala*/

/*$ADODB["password"] = "password";
$ADODB["database_name"] = "calidad";
$ADODB["user"] = "phpuser";*/

$ADODB["database_type"] = "mysqli";
//print  "{$ADODB["adodb_path"]}/adodb.inc.php";
include("{$ADODB["adodb_path"]}/adodb.inc.php");

$dbCalidad = NewADOConnection($ADODB["database_type"]);
$dbCalidad->Connect($ADODB["host"], $ADODB["user"], $ADODB["password"], $ADODB["database_name"]);

$ADODB["database_name"] = "media";
$db = NewADOConnection($ADODB["database_type"]);
$db->Connect($ADODB["host"], $ADODB["user"], $ADODB["password"], $ADODB["database_name"]);

$db->Execute("SET GLOBAL group_concat_max_len=15360");

error_reporting(E_ALL);
ini_set("display_errors", 1);

/*
if (!function_exists('json_encode'))
{
  function json_encode($a=false)
  {
    if (is_null($a)) return 'null';
    if ($a === false) return 'false';
    if ($a === true) return 'true';
    if (is_scalar($a))
    {
      if (is_float($a))
      {
        // Always use "." for floats.
        return floatval(str_replace(",", ".", strval($a)));
      }

      if (is_string($a))
      {
        static $jsonReplaces = array(array("\\", "/", "\n", "\t", "\r", "\b", "\f", '"'), array('\\\\', '\\/', '\\n', '\\t', '\\r', '\\b', '\\f', '\"'));
        return '"' . str_replace($jsonReplaces[0], $jsonReplaces[1], $a) . '"';
      }
      else
        return $a;
    }
    $isList = true;
    for ($i = 0, reset($a); $i < count($a); $i++, next($a))
    {
      if (key($a) !== $i)
      {
        $isList = false;
        break;
      }
    }
    $result = array();
    if ($isList)
    {
      foreach ($a as $v) $result[] = json_encode($v);
      return '[' . join(',', $result) . ']';
    }
    else
    {
      foreach ($a as $k => $v) $result[] = json_encode($k).':'.json_encode($v);
      return '{' . join(',', $result) . '}';
    }
  }
}
*/
