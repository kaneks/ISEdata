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

        $result=$this->secureLogin($email,$password);

        if ($result->num_rows == 1) {
          
            $row = mysqli_fetch_array($result);
            $p = new OAuthProvider();
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

    //part that handles the secure sql execution
    private function secureLogin($emailData,$passwordData){
        $sql = "SELECT * FROM logintable WHERE Email= ? AND Password= ?";
        if($stmt = $this->_connection->prepare($sql)){

            $stmt->bind_param("ss",$emailData,$passwordData);
            $stmt->execute();
            $stmt->bind_result($returnVal);
            $stmt->fetch();
            $stmt->close();
            return $returnVal;
        }
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
        $sql1 = "SELECT id FROM logintable WHERE token=" . $token;
        $result = mysqli_query($this->_connection,$sql1);
        if($result){
            $row = mysqli_fetch_array($result);
            #echo "id is ".$row["id"];
        } else {
            #echo "Can't find id";
            return json_encode(array("result" => 3));
        }
        $sql = "UPDATE coursetable SET ADME='" . $adme . "', AERO='" . $aero . "', ICE='" . $ice . "', NANO='" . $nano . "'WHERE id=" . $row["id"];
        $result = mysqli_query($this->_connection,$sql1);
        if ($result) {
            //submit successfully
            //code: 1
            $this->updateLog($row["id"], 1);
            #echo "Record updated successfully";
            return json_encode(array("result" => 1));
        } else {
            //database error can't update
            //code: 2
            $action = "2";
            $this->updateLog($row["id"], 2);
            #echo "Error updating record: ".$sql . "<br>" .$this->_connection->error;
            return json_encode(array("result" => 2));
        }
    }

    //checks the token if matches the generated token
    private function checkToken($token)
    {
        $sql = "SELECT * FROM logintable WHERE token=" . $token;
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
        $sql = "INSERT INTO log (id, action, time) VALUES ('" . $id . "', '" . $action . "', '" . $date . "')";
        if ($this->_connection->query($sql) == TRUE) {
            echo "New record created successfully";
        } else {
            echo "Error insert record: ".$sql . "<br>" .$this->_connection->error;
        }
    }

    /*
     *getData( TOKEN ) send in token and find the student with matching token
     * and return JSON{ result, name, surname, ice }
     * */
    public function getData($token)
    {

            $sql1 = "SELECT id FROM logintable WHERE token=" . $token;
            $result = mysqli_query($this->_connection,$sql1);
            if($result){
                $row = mysqli_fetch_array($result);
                #echo "id is ".$row["id"];
            } else {
                #echo "Can't find id";
                return json_encode(array("result" => 3));
            }
            $sql = "SELECT * FROM coursetable WHERE id=" . $row["id"];
            $result1 = mysqli_query($this->_connection,$sql);
            $row1 = mysqli_fetch_array($result1);
            if ($result) {
                //success code:1
                $action = "1";
                return json_encode(array("result" => $action, "Title" => $row1["Title"], "FirstName" => $row1["FirstName"], "SurName" => $row1["SurName"], "adme" => $row1["ADME"], "aero" => $row1["AERO"], "ice" => $row1["ICE"], "nano" => $row1["NANO"]));
            }
            //data base error code:2
            $action = "2";
            return json_encode(array("result" => $action));

    }


}