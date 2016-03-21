<?php
/**
 * Created by PhpStorm.
 * User: Sasinn
 * Date: 18/03/2016
 * Time: 21:33
 */
require_once("curl.php");

$params = array(
    "email" => $_POST["email"],
    "token" => $_POST["token"],
    "adme" => $_POST["adme"],
    "aero" => $_POST["aero"],
    "ice" => $_POST["ice"],
    "nano" => $_POST["nano"]
);

echo httpPost(curPageURL()."/ISEdata/api/update.php",$params);

?>