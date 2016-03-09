<?php
/**
 * Created by PhpStorm.
 * User: Sasinn
 * Date: 08/03/2016
 * Time: 23:25
 */
require_once("/classes/Database.php");
require_once("/classes/ISEDatabase.php");

$db = new ISEDatabase();
echo $db->getData($_POST["token"]);

?>