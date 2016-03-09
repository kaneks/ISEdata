<?php

/**
 * Created by PhpStorm.
 * User: Kaneks
 * Date: 3/8/2016 AD
 * Time: 10:42 PM
 */
class ISEDatabase Extends Database
{
    /*register() inserts new email and password into database
    note also need to implement security check*/
    public function register($email, $password)
    {
        $sql = "INSERT INTO logintable (Email, Password) VALUES ('" . $email . "', '" . $password . "')";
        $result = $this->_connection->query($sql);

    }

    /*login( email, password) is for logging into webserver and generating token for user.
        function returns JSON{status, token, error}
        status: 1 = user already submitted in before
        status: 2 = user has never submitted
        status: 3 = invalid email or password

        token: null if error

        error: 0 if no error
                -1 if invalid email or password
    */
    public function login($email, $password)
    {   //set to lowercase
        $email = strtolower($email);
        $sql = "SELECT * FROM logintable WHERE Email='" . $email . "' AND Password='" . $password . "'";
        $result = $this->_connection->query($sql);
        if ($result->num_rows == 1) {
            $p = new OAuthProvider();
            $row = $result->fetch_assoc();
            $token = $p->generateToken(32);
            $sql1 = "UPDATE logintable SET token='" . $token . "' WHERE Email='" . $email . "'";
            $this->_connection->query($sql1);
            if ($this->checkIfUpdated($row["id"])) {
                //user has already submitted in before
                return json_encode(array("status" => "1", "token" => $token, "error" => "0"));
            } else {
                //user has never submitted
                return json_encode(array("status" => "2", "token" => $token, "error" => "0"));
            }
        } else {
            //invalid email or password
            return json_encode(array("status" => "3", "token" => null, "error" => "-1"));
        }
    }

    //row is array that checks data if student has already selected submitted his choice of majar
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

    /*
     *update( $token,$adme,$aero,$ice,$nano ) receives JSON from screen major selection page and update the student's choice on the database
     * Receives JSON{ADME, AERO, ICE, NANO}
     * returns JSON{ result }
     * data is 1, 2, 3, 4 according to ranking selection
     * error: 1 if submit successfully
     * error: 2 if database error can't update
     * error: 3 if wrong token can't update
     * */

    public function update($token, $adme, $aero, $ice, $nano)
    {

        if ($this->checkToken($token) == true) {
            $sql1 = "SELECT id FROM logintable WHERE token='" . $token . "'";
            $result = $this->_connection->query($sql1);
            $row = $result->fetch_assoc();
            $sql = "UPDATE coursetable SET ADME=" . $adme . ", AERO=" . $aero . ", ICE=" . $ice . ", NANO=" . $nano . " WHERE id=" . $row["id"] . "";
            if ($this->_connection->query($sql) == TRUE) {
                //submit successfully
                //code: 1
                $action = "1";
                $this->updateLog($row["id"], $action);
                echo "Record updated successfully";
                return json_encode(array("result" => $action));
            } else {
                //database error can't update
                //code: 2
                $action = "2";
                $this->updateLog($row["id"], $action);
                echo "Error updating record: ";
                return json_encode(array("result" => $action));
            }
        } else {
            //wrong token can't update
            //code: 3
            $action = "3";
            echo "Wrong token";
            return json_encode(array("result" => $action));
        }
    }

    //checks the token if matches the generated token
    private function checkToken($token)
    {
        $sql = "SELECT * FROM logintable WHERE token='" . $token . "'";
        if ($this->_connection->query($sql) == TRUE) {
            //legit token
            return true;
        }
        //non-legit token
        return false;
    }


    private function updateLog($id, $action)
    {
        $date = date('Y/m/d H:i:s');
        $sql = "INSERT INTO tableName3 (id, action, time) VALUES ('" . $id . "', '" . $action . "', '" . $date . "')";
        if ($this->_connection->query($sql) == TRUE) {
            echo "New record created successfully";
        } else {
            echo "Error insert record: ";
        }
    }

    /*
     *getData( TOKEN ) send in token and find the student with matching token
     * and return JSON{ result, name, surname, ice }
     * */
    public function getData($token)
    {
        if ($this->checkToken($token) == true) {
            $sql1 = "SELECT id FROM logintable WHERE token=''" . $token . "'";
            $result = $this->_connection->query($sql1);
            $row = $result->fetch_assoc();
            $sql = "SELECT * FROM coursetable WHERE id='" . $row["id"] . "'";
            $result1 = $this->_connection->query($sql);
            $row1 = $result1->fetch_assoc();
            if ($result->num_rows != 0) {
                //success code:1
                $action = "1";
                return json_encode(array("result" => $action, "name" => $row1["name"], "surname" => $row1["surname"], "ice" => $row1["ice"], "adme" => $row1["adme"], "aero" => $row1["aero"], "nano" => $row1["nano"]));
            }
            //data base error code:2
            $action = "2";
            return json_encode(array("result" => $action));
        }
        //wrong token error code:3
        $action = "3";
        return json_decode(array("result" => $action));
    }


}