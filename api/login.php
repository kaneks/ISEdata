<?php
/**
 * Created by PhpStorm.
 * User: Sasinn
 * Date: 08/03/2016
 * Time: 23:23
 */

require_once("curl.php");

$params = array(
    "email" => $_POST["email"],
    "password" => $_POST["password"],
);

echo httpPost("http://localhost:85/ISEdata/api/curlLogin.php",$params);

?>