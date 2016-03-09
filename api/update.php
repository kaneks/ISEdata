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
echo $db->update($_POST["token"],(int) $_POST["adme"],(int) $_POST["aero"],(int) $_POST["ice"],(int) $_POST["nano"]);

?>