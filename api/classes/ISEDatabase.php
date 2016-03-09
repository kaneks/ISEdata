<?php

/**
 * Created by PhpStorm.
 * User: Kaneks
 * Date: 3/8/2016 AD
 * Time: 10:42 PM
 */
class ISEDatabase Extends Database
{

    public function register($email, $password){
        $sql = "INSERT INTO logintable (Email, Password) VALUES ('".$email."', '".$password."')";
        $result = $this->_connection->query($sql);
    }

    public function login($email, $password)
    {

        $sql = "SELECT * FROM logintable WHERE Email='" . $email . "' AND Password='" . $password . "'";
        $result = $this->_connection->query($sql);
        if ($result->num_rows == 1) {
            $row = $result->fetch_assoc();
            $token = OAuthProvider::generateToken();
            if ($this->checkIfUpdated($row["id"])) {
                //user has already submitted in before
                return json_encode(array("status" => "1","token" => $token,"error" => null));
            } else {
                //user has never submitted
                return json_encode(array("status" => "2","token" => $token,"error" => null));
            }
        } else {
            //invalid email or password
            return json_encode(array("status" => "3","token" => null,"error" => "invalid email or password"));
        }
    }

    private function checkIfUpdated($id)
    {
        $sql = "SELECT * FROM coursetable WHERE id='" . $id . "'";
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
            $sql1 = "SELECT id FROM logintable WHERE token=''" . $info->token . "'";
            $result = $this->_connection->query($sql1);
            $row = $result->fetch_assoc();
            $sql = "UPDATE coursetable SET ADME='" . $info->adme . "', AERO='" . $info->aero . "', ICE='" . $info->ice . "', NANO='" . $info->nano . "' WHERE id='" . $row["id"] . "'";
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
        $sql = "SELECT * FROM logintable WHERE token='" . $token . "'";
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


    function getData($token)
    {

        if ($this->checkToken($token) == true) {
            $sql1 = "SELECT id FROM logintable WHERE token=''" . $token . "'";
            $result = $this->_connection->query($sql1);
            $row = $result->fetch_assoc();
            $sql = "SELECT * FROM coursetable WHERE id='" . $row["id"] . "'";
            $result1 = $this->_connection->query($sql);
            $row1 = $result1->fetch_assoc();
            if ($result->num_rows != 0) {
                $action = "success";
                return json_encode(array("result" => $action, "name" => $row1["name"], "surname" => $row1["surname"], "ice" => $row1["ice"], "adme" => $row1["adme"], "aero" => $row1["aero"], "nano" => $row1["nano"]));
            }
            $action = "data base error";
            return json_encode(array("result" => $action));
        }
        $action = "wrong token";
        return json_decode(array("result" => $action));
    }


}