<?php
/**
 * Created by PhpStorm.
 * User: Sasinn
 * Date: 08/03/2016
 * Time: 23:23
 */
echo "You have entered ".$_POST["email"]." as your email.";
echo "You have entered ".$_POST["password"]." as your password.";
$db = new ISEDatabase();
ISEDatabase::getInstance()->register($_POST["email"], $_POST["password"]);

?>