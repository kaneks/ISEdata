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
        $sql = "INSERT INTO login (Email, Password) VALUES ('" . $email . "', '" . $password . "')";
        $result = $this->_connection->query($sql);

    }

    /*login( email, password) is for logging into webserver and generating token for user.
        function returns JSON{status, token, error}
        status: 0 = user already submitted in before
        status: 1 = user has never submitted
        status: 2 = invalid email or password

        token: null if error

        error: 0 if no error
                -1 if invalid email or password
    */
    public function login($email, $password)
    {   //set to lowercase
        $email = strtolower($email);
        $sql = "SELECT * FROM login WHERE Email='" . $email . "' AND Password='" . $password . "'";
        //$result = $this->secureLogin($email, $password);
        $result = mysqli_query($this->_connection, $sql);
        if (mysqli_num_rows($result) == 1) {
            $row = mysqli_fetch_array($result);
            $id = $row["id"];
            //$p = new OAuthProvider();
            //$token = $p->generateToken(32);
            $token = uniqid('', true);
            $sql = "UPDATE login SET token='" . $token . "' WHERE Email='" . $email . "'";
            $result = mysqli_query($this->_connection, $sql);
            if ($result) {
                $sql = "SELECT * FROM course WHERE id=" . $id;
                $result = mysqli_query($this->_connection, $sql);
                if (mysqli_num_rows($result) == 1) {
                    $row = mysqli_fetch_array($result);
                    if ($this->checkIfUpdated($row["id"])) {
                        //user has already submitted in before
                        if ($this->updateLog($row["id"], "login success, user has logged in before")) {
                            return json_encode(array("status" => 0, "log_result" => 0, "token" => $token, "regisNum" => $row["id"]
                            , "title" => $row["Title"], "name" => $row["FirstName"], "surname" => $row["SurName"]
                            , "adme" => $row["ADME"], "aero" => $row["AERO"], "ice" => $row["ICE"], "nano" => $row["NANO"]));
                        }
                        return json_encode(array("status" => 0, "log_result" => 1, "token" => $token, "regisNum" => $row["id"]
                        , "title" => $row["Title"], "name" => $row["FirstName"], "surname" => $row["SurName"]
                        , "adme" => $row["ADME"], "aero" => $row["AERO"], "ice" => $row["ICE"], "nano" => $row["NANO"]));
                    } else {
                        //user has never submitted
                        if ($this->updateLog($row["id"], "login success, first time")) {
                            return json_encode(array("status" => 1, "log_result" => 0, "token" => $token, "regisNum" => $row["id"]
                            , "title" => $row["Title"], "name" => $row["FirstName"], "surname" => $row["SurName"]
                            , "adme" => $row["ADME"], "aero" => $row["AERO"], "ice" => $row["ICE"], "nano" => $row["NANO"]));
                        }
                        return json_encode(array("status" => 1, "log_result" => 1, "token" => $token, "regisNum" => $row["id"]
                        , "title" => $row["Title"], "name" => $row["FirstName"], "surname" => $row["SurName"]
                        , "adme" => $row["ADME"], "aero" => $row["AERO"], "ice" => $row["ICE"], "nano" => $row["NANO"]));
                    }
                }
            }
        } else {
            //invalid email or password
            if($this->updateLog("-1", "user entered invalid email or password : " . $email . " " . $password)){
                return json_encode(array("status" => 2, "log_result" => 0, "message" => "Invalid email or password.", "token" => null, "regisNum" => null
                , "title" => null, "name" => null, "surname" => null
                , "adme" => null, "aero" => null, "ice" => null, "nano" => null));
            }
            return json_encode(array("status" => 2, "log_result" => 1, "message" => "Invalid email or password.", "token" => null, "regisNum" => null
            , "title" => null, "name" => null, "surname" => null
            , "adme" => null, "aero" => null, "ice" => null, "nano" => null));
        }
    }


    //row is array that checks data if student has already selected submitted his choice of majar
    private function checkIfUpdated($id)
    {
        $sql = "SELECT * FROM course WHERE id='" . $id . "'";
        $result = mysqli_query($this->_connection, $sql);
        if (mysqli_num_rows($result) == 1) {
            $row = mysqli_fetch_array($result);
            if ($row["ADME"] != 0 || $row["AERO"] != 0 || $row["ICE"] != 0 || $row["NANO"] != 0) {
                return true;
            }
        }
        return false;
    }

    //part that handles the secure sql execution
    private function secureLogin($emailData, $passwordData)
    {
        $sql = "SELECT * FROM login WHERE Email=? AND Password=?";
        if ($stmt = $this->_connection->prepare($sql)) {
            $stmt->bind_param("ss", $emailData, $passwordData);
            $stmt->execute();
            $stmt->store_result();
            //need to know ordering of database table
            //will modify later
            $stmt->bind_result($id, $email);
            $stmt->fetch();
            $stmt->close();
            //part that will be modify later on once mickey arives
            return array("id" => $id, "email" => $email);
        }
    }


    /*
     *update( $token,$adme,$aero,$ice,$nano ) receives JSON from screen major selection page and update the student's choice on the database
     * Receives JSON{ADME, AERO, ICE, NANO}
     * returns JSON{ result }
     * data is 1, 2, 3, 4 according to ranking selection
     * error: 0 if submit successfully
     * error: 1 if database error can't update
     * error: 2 if wrong token can't update
     * */

    public function update($email, $token, $adme, $aero, $ice, $nano)
    {
        $sql = "SELECT id FROM login WHERE token='" . $token . "' AND Email='" . $email . "'";
        $result = mysqli_query($this->_connection, $sql);
        if (mysqli_num_rows($result) == 1) {
            $row = mysqli_fetch_array($result);
            $sql = "UPDATE course SET ADME=" . $adme . ", AERO=" . $aero . ", ICE=" . $ice . ", NANO=" . $nano . " WHERE id=" . $row["id"];
            $result = mysqli_query($this->_connection, $sql);
            if ($result) {
                $sql = "SELECT * FROM course WHERE id=" . $row["id"];
                $result = mysqli_query($this->_connection, $sql);
                if (mysqli_num_rows($result) == 1) {
                    $row = mysqli_fetch_array($result);
                    if ($this->updateLog($row["id"], "user has updated record.")) {
                        //submit successful
                        //log successful
                        //code: 0, 0
                        return json_encode(array("result" => 0, "log_result" => 0));
                    }
                    //submit successful
                    //log unsuccessful
                    //code: 0, 1
                    return json_encode(array("result" => 0, "log_result" => 1));
                }
            }
        } else {
            if ($this->updateLog("", "wrong token received")) {
                //cant find id with matching token and email
                //log successful
                //code: 1, 0
                return json_encode(array("result" => 1, "log_result" => 0));
            }
            //cant find id with matching token and email
            //log unsuccessful
            //code: 1, 1
            return json_encode(array("result" => 1, "log_result" => 1));
        }
        //database error can't update
        //no log
        //code: 2
        return json_encode(array("result" => 2, "log_result" => 0));
    }

    public function secureUpdate($email, $token, $adme, $aero, $ice, $nano)
    {
        $result = $this->secureConnect($token, $email);
        if ($result) {
            $row = $result;
            if (count($result) != 1) {
                if ($this->updateLog("", "wrong token can't find id")) {
                    //cant find id with matching token and email
                    //log successful
                    //code: 1, 0
                    return json_encode(array("result" => 1, "log_result" => 0));
                }
                //cant find id with matching token and email
                //log unsuccessful
                //code: 1, 1
                return json_encode(array("result" => 1, "log_result" => 1));
            } else {
                $result = $this->secureUpdate($adme, $aero, $ice, $nano, $row["id"]);
                if ($result) {
                    $sql = "SELECT * FROM course WHERE id=" . $row["id"];
                    $result = mysqli_query($this->_connection, $sql);
                    if ($result) {
                        $row = mysqli_fetch_array($result);
                        if ($this->updateLog($row["id"], $row["FirstName"] . " " . $row["SurName"] . " updated record.")) {
                            //submit successful
                            //log successful
                            //code: 0, 0
                            return json_encode(array("result" => 0, "log_result" => 0));
                        }
                        //submit successful
                        //log unsuccessful
                        //code: 0, 1
                        return json_encode(array("result" => 0, "log_result" => 1));
                    }
                }
            }
        }
        //database error can't update
        //no log
        //code: 2
        return json_encode(array("result" => 2, "log_result" => 0));
    }
    /*
    private function secureConnect($tokenData,$emailData){
        $sql = "SELECT id FROM login WHERE token=? AND Email=?";
        $stmt = mysqli_prepare($this->_connection, $sql);
        if($stmt){
            $stmt->bind_param("ss",$tokenData,$emailData);
            $stmt->execute();
            $stmt->store_result();
            $stmt->bind_result($returnVal);
            $stmt->fetch();
            $stmt->close();
            if($returnVal != null){
                TRUE;
            }
            #return array("id"=>$returnVal);
        }
        return FALSE;
    }

    private function secureUpdate($adme, $aero, $ice, $nano,$id){
        $sql = "UPDATE course SET ADME=?, AERO=?, ICE=?, NANO=? WHERE id=?";
        if($stmt = $this->_connection->prepare($sql)){
            $stmt->bind_param("ssssi",$adme,$aero,$ice,$nano,$id);
            if($stmt->execute()){
                return true;
            }
            else{
                return false;
            }

        }
    }
    */

    //checks the token if matches the generated token
    private function checkToken($token)
    {
        $sql = "SELECT * FROM login WHERE token=" . $token;
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
        $sql = "INSERT INTO log (id, action, time) VALUES (" . $id . ", '" . $action . "', '" . $date . "')";
        $result = mysqli_query($this->_connection, $sql);
        return $result;
    }

    /*
     *getData( TOKEN ) send in token and find the student with matching token
     * and return JSON{ result, name, surname, ice }
     * */
    public function getData($token)
    {
        $sql1 = "SELECT id FROM login WHERE token=" . $token;
        $result = mysqli_query($this->_connection, $sql1);
        if ($result) {
            $row = mysqli_fetch_array($result);
            if (mysqli_num_rows($result) != 1) {
                if ($this->updateLog($row["id"], "can't find token")) {
                    return json_encode(array("result" => 1, "log_result" => 0));
                }
                return json_encode(array("result" => 1, "log_result" => 1));
            } else {
                $sql = "SELECT * FROM course WHERE id=" . $row["id"];
                $result = mysqli_query($this->_connection, $sql);
                if ($result) {
                    $row1 = mysqli_fetch_array($result);
                    if ($this->updateLog($row["id"], "getData called for " . $row["FirstName"] . " " . $row["LastName"] . ".")) {
                        return json_encode(array("result" => 0, "log_result" => 0, "id" => $row1["id"], "Title" => $row1["Title"], "FirstName" => $row1["FirstName"], "SurName" => $row1["SurName"], "adme" => $row1["ADME"], "aero" => $row1["AERO"], "ice" => $row1["ICE"], "nano" => $row1["NANO"]));
                    }
                    return json_encode(array("result" => 0, "log_result" => 1, "id" => $row1["id"], "Title" => $row1["Title"], "FirstName" => $row1["FirstName"], "SurName" => $row1["SurName"], "adme" => $row1["ADME"], "aero" => $row1["AERO"], "ice" => $row1["ICE"], "nano" => $row1["NANO"]));
                }
            }
        }
        $this->updateLog("", "wrong token can't find id");
        return json_encode(array("result" => 2, "log_result" => 0));
    }


}