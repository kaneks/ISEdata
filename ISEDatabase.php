<?php

/**
 * Created by PhpStorm.
 * User: Kaneks
 * Date: 3/8/2016 AD
 * Time: 10:42 PM
 */
class ISEDatabase Extends Database
{

    public function login($email, $password)
    {
        $sql = "SELECT * FROM tableName1 WHERE Email='" . $email . "' AND Password='" . $password . "'";
        $result = $this->_connection->query($sql);
        if ($result->num_rows == 1) {
            $row = $result->fetch_assoc();
            $token = OAuthProvider::generateToken();
            if ($this->checkIfUpdated($row["id"])) {
                //user has already submitted in before
            } else {
                //user has never submitted
            }
        } else {
            //invalid email or password
        }
    }

    private function checkIfUpdated($id)
    {
        $sql = "SELECT * FROM tableName2 WHERE id='" . $id . "'";
        $result = $this->_connection->query($sql);
        $row = $result->fetch_assoc();
        if ($row["ADME"] == 0 && $row["AERO"] == 0 && $row["ICE"] == 0 && $row["NANO"] == 0) {
            return false;
        }
        return true;
    }

    public function update($json)
    {
        $info = json_decode($json);
        if ($this->checkToken($info->token) == true) {
            $sql1 = "SELECT id FROM tableName1 WHERE token=''" . $info->token . "'";
            $result = $this->_connection->query($sql1);
            $row = $result->fetch_assoc();
            $sql = "UPDATE tableName2 SET ADME='" . $info->adme . "', AERO='" . $info->aero . "', ICE='" . $info->ice . "', NANO='" . $info->nano . "' WHERE id='" . $row["id"] . "'";
            if ($this->_connection->query($sql) == TRUE) {
                $action = "submit successfully";
                $this->updateLog($row["id"], $action);
                echo "Record updated successfully";
                return json_encode(array("result" => $action));
            } else {
                $action = "database error can't update";
                $this->updateLog($row["id"], $action);
                echo "Error updating record: ";
                return json_encode(array("result" => $action));
            }
        } else {
            $action = "wrong token can't update";
            echo "Wrong token";
            return json_encode(array("result" => $action));
        }
    }

    public function checkToken($token)
    {
        $sql = "SELECT * FROM tableName1 WHERE token='" . $token . "'";
        $result = $this->_connection->query($sql);
        if ($result->num_rows == 1) {
            //legit token
            return true;
        }
        //non-legit token
        return false;
    }

    public function updateLog($id, $action)
    {
        $date = date('Y/m/d H:i:s');
        $sql = "INSERT INTO tableName3 (id, action, time) VALUES ('" . $id . "', '" . $action . "', '" . $date . "')";
        if ($this->_connection->query($sql) == TRUE) {
            echo "New record created successfully";
        } else {
            echo "Error insert record: ";
        }
    }


    function getData($json)
    {
        $info = json_decode($json);
        if ($this->checkToken($info->token) == true) {
            $sql1 = "SELECT id FROM tableName1 WHERE token=''" . $info->token . "'";
            $result = $this->_connection->query($sql1);
            $row = $result->fetch_assoc();
            $sql = "SELECT * FROM tableName2 WHERE id='" . $row["id"] . "'";
            $result1 = $this->_connection->query($sql);
            $row1 = $result1->fetch_assoc();
            return json_encode(array("name" => $row1["name"], "surname" => $row1["surname"], "ice" => $row1["ice"], "adme" => $row1["adme"], "aero" => $row1["aero"], "nano" => $row1["nano"]));
        }
    }


}