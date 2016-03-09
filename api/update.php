<?php
/**
 * Created by PhpStorm.
 * User: Sasinn
 * Date: 08/03/2016
 * Time: 23:23
 */
require_once("/classes/Database.php");
require_once("/classes/ISEDatabase.php");

$db = new ISEDatabase();
$db->update($_POST["token"],$_POST["adme"],$_POST["aero"],$_POST["ice"],$_POST["nano"]);

?>