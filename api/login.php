<?php
/**
 * Created by PhpStorm.
 * User: Sasinn
 * Date: 18/03/2016
 * Time: 16:02
 */
require_once("classes/Database.php");
require_once("classes/ISEDatabase.php");
#echo "You have entered ".$_POST["email"]." as your email.";
#echo "You have entered ".$_POST["password"]." as your password.";
$db = new ISEDatabase();
echo $db->login($_POST["email"], $_POST["password"]);


?>