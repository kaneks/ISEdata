<?php

/**
 * Created by PhpStorm.
 * User: Kaneks
 * Date: 3/8/2016 AD
 * Time: 10:42 PM
 */
class ISEDatabase Extends Database
{
    private $MAX_ADME = 50;
    private $MAX_AERO = 30;
    private $MAX_ICE = 60;
    private $MAX_NANO = 60;

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
        //$sql = "SELECT * FROM login WHERE Email='" . $email . "' AND Password='" . $password . "'";
        //$result = $this->secureLogin($email, $password);
        //$result = mysqli_query($this->_connection, $sql);
        $resultOfLogin = $this->secureLogin($email, $password);
        if ($resultOfLogin["id"] != null) {
            $row = $resultOfLogin;
            $id = $row["id"];
            //for checking of logic


            //$p = new OAuthProvider();
            //$token = $p->generateToken(32);
            $token = uniqid('', true);
            //$sql = "UPDATE login SET token='" . $token . "' WHERE Email='" . $email . "'";
            // $result = mysqli_query($this->_connection, $sql);
            $updateResult = $this->secureLoginUpdate($token, $email);
            if ($updateResult) {
                $sql = "SELECT * FROM major WHERE id=" . $id;
                $result = mysqli_query($this->_connection, $sql);
                if (mysqli_num_rows($result) == 1) {
                    $row = mysqli_fetch_array($result);
                    if ($this->checkIfUpdated($row["id"])) {
                        //user has already submitted in before
                        if ($this->updateLog($row["id"], "login success, user has logged in before")) {
                            return json_encode(array("result" => 0, "log_result" => 0, "token" => $token, "regisNum" => $row["id"]
                            , "title" => $row["Title"], "name" => $row["FirstName"], "surname" => $row["SurName"]
                            , "adme" => $row["ADME"], "aero" => $row["AERO"], "ice" => $row["ICE"], "nano" => $row["NANO"]));
                        }
                        return json_encode(array("result" => 0, "log_result" => 1, "token" => $token, "regisNum" => $row["id"]
                        , "title" => $row["Title"], "name" => $row["FirstName"], "surname" => $row["SurName"]
                        , "adme" => $row["ADME"], "aero" => $row["AERO"], "ice" => $row["ICE"], "nano" => $row["NANO"]));
                    } else {
                        //user has never submitted
                        if ($this->updateLog($row["id"], "login success, first time")) {
                            return json_encode(array("result" => 1, "log_result" => 0, "token" => $token, "regisNum" => $row["id"]
                            , "title" => $row["Title"], "name" => $row["FirstName"], "surname" => $row["SurName"]
                            , "adme" => $row["ADME"], "aero" => $row["AERO"], "ice" => $row["ICE"], "nano" => $row["NANO"]));
                        }
                        return json_encode(array("result" => 1, "log_result" => 1, "token" => $token, "regisNum" => $row["id"]
                        , "title" => $row["Title"], "name" => $row["FirstName"], "surname" => $row["SurName"]
                        , "adme" => $row["ADME"], "aero" => $row["AERO"], "ice" => $row["ICE"], "nano" => $row["NANO"]));
                    }
                }
            }
        } else {
            //invalid email or password
            if ($this->updateLog("-1", "user entered invalid email or password : " . $email . " " . $password)) {
                return json_encode(array("result" => 2, "log_result" => 0, "message" => "Invalid email or password.", "token" => null, "regisNum" => null
                , "title" => null, "name" => null, "surname" => null
                , "adme" => null, "aero" => null, "ice" => null, "nano" => null));
            }
            return json_encode(array("result" => 2, "log_result" => 1, "message" => "Invalid email or password.", "token" => null, "regisNum" => null
            , "title" => null, "name" => null, "surname" => null
            , "adme" => null, "aero" => null, "ice" => null, "nano" => null));
        }
    }

    //row is array that checks data if student has already selected submitted his choice of majar

    private function checkIfUpdated($id)
    {
        $sql = "SELECT * FROM major WHERE id='" . $id . "'";
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
        $sql = "SELECT id FROM login WHERE Email=? AND Password=?";
        if ($stmt = $this->_connection->prepare($sql)) {
            $stmt->bind_param("ss", $emailData, $passwordData);
            $stmt->execute();
            $stmt->store_result();
            //need to know ordering of database table
            //will modify later
            $stmt->bind_result($id);
            $stmt->fetch();
            $stmt->close();
            //part that will be modify later on once mickey arives
            return array("id" => $id,);
        }
    }

    private function secureLoginUpdate($tokenData, $emailData)
    {
        $sql = "UPDATE login SET token=? WHERE Email=?";
        if ($stmt = $this->_connection->prepare($sql)) {
            $stmt->bind_param("ss", $tokenData, $emailData);
            $isOk = $stmt->execute();
            $stmt->close();
            //part that will be modify later on once mickey arives
            return $isOk;
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
        // $sql = "SELECT id FROM login WHERE token='" . $token . "' AND Email='" . $email . "'";
        // $result = mysqli_query($this->_connection, $sql);

        $result = $this->secureConnect($token, $email);
        if ($result["id"] != null) {

            $row = $result;
            //$sql = "UPDATE course SET ADME=" . $adme . ", AERO=" . $aero . ", ICE=" . $ice . ", NANO=" . $nano . " WHERE id=" . $row["id"];
            //$result = mysqli_query($this->_connection, $sql);
            $result = $this->secureUpdate($adme, $aero, $ice, $nano, $row["id"]);
            if ($result) {
                $sql = "SELECT * FROM major WHERE id=" . $row["id"];
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
            if ($this->updateLog("-1", "wrong token to update " . $email . " " . $token)) {
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

    private function secureConnect($tokenData, $emailData)
    {
        $sql = "SELECT id FROM login WHERE token=? AND Email=?";
        if ($stmt = $this->_connection->prepare($sql)) {
            $stmt->bind_param("ss", $tokenData, $emailData);
            $stmt->execute();
            $stmt->store_result();
            //need to know ordering of database table
            //will modify later
            $stmt->bind_result($id);
            $stmt->fetch();
            $stmt->close();
            //part that will be modify later on once mickey arives
            return array("id" => $id);
        }
    }

    private function secureUpdate($adme, $aero, $ice, $nano, $id)
    {
        $sql = "UPDATE major SET ADME=?, AERO=?, ICE=?, NANO=? WHERE id=?";
        if ($stmt = $this->_connection->prepare($sql)) {
            $stmt->bind_param("iiiii", $adme, $aero, $ice, $nano, $id);
            $isOk = $stmt->execute();
            $stmt->close();
            return $isOk;
        }
    }

    private function updateLog($id, $action)
    {
        date_default_timezone_set("Asia/Bangkok");
        $date = date('Y/m/d H:i:s');
        $sql = "INSERT INTO log (id, action, time) VALUES (" . $id . ", '" . $action . "', '" . $date . "')";
        $result = mysqli_query($this->_connection, $sql);
        return $result;
    }

    /*
     *getData( Rank ) send in rank and find the student with matching token
     * and return JSON{ result,title, regisNum , name, surname, adme, aero, ice, nano }
     * */

    public function getStudentById($id)
    {
        $sql = "SELECT * FROM major WHERE id=" . $id;
        $result = mysqli_query($this->_connection, $sql);
        if ($result) {
            $row = mysqli_fetch_array($result);
            if ($this->updateLog($id, "Student data requested.")) {
                return json_encode(array("result" => 0, "log_result" => 0, "regisNum" => $row["id"]
                , "title" => $row["Title"], "name" => $row["FirstName"], "surname" => $row["SurName"]
                , "adme" => $row["ADME"], "aero" => $row["AERO"], "ice" => $row["ICE"], "nano" => $row["NANO"]));
            }
            return json_encode(array("result" => 0, "log_result" => 1, "regisNum" => $row["id"]
            , "title" => $row["Title"], "name" => $row["FirstName"], "surname" => $row["SurName"]
            , "adme" => $row["ADME"], "aero" => $row["AERO"], "ice" => $row["ICE"], "nano" => $row["NANO"]));
        } else {
            if ($this->updateLog(-1, "Invalid id form rank." . $id)) {
                return json_encode(array("result" => 1, "log_result" => 0, "regisNum" => null
                , "title" => null, "name" => null, "surname" => null
                , "adme" => null, "aero" => null, "ice" => null, "nano" => null));
            }
            return json_encode(array("result" => 1, "log_result" => 1, "regisNum" => null
            , "title" => null, "name" => null, "surname" => null
            , "adme" => null, "aero" => null, "ice" => null, "nano" => null));
        }
    }

    public function getStudentByRank($rank)
    {
        $id = $this->getId($rank);
        //can't find rank
        if ($id == false) {
            if ($this->updateLog(-1, "Invalid rank entered. " . $rank)) {
                return json_encode(array("result" => 2, "log_result" => 0, "regisNum" => null
                , "title" => null, "name" => null, "surname" => null
                , "adme" => null, "aero" => null, "ice" => null, "nano" => null));
            }
            return json_encode(array("result" => 2, "log_result" => 1, "regisNum" => null
            , "title" => null, "name" => null, "surname" => null
            , "adme" => null, "aero" => null, "ice" => null, "nano" => null));
        } else {
            return $this->getStudentById($id);
        }
    }

    private function getId($rank)
    {
        $sql = "SELECT id FROM rank WHERE rank=" . $rank;
        $result = mysqli_query($this->_connection, $sql);
        if ($result) {
            $row = mysqli_fetch_array($result);
            return $row["id"];
        }
        return false;
    }

    public function setStudent($id, $status)
    {
        $sql = "UPDATE admission SET status='" . $status . "' WHERE id=" . $id;
        $result = mysqli_query($this->_connection, $sql);
        if ($result) {
            if ($this->updateLog($id, "Student status updated to " . $status . ".")) {
                return json_encode(array("result" => 0, "log_result" => 0));
            }
            return json_encode(array("result" => 0, "log_result" => 1));
        } else {
            if ($this->updateLog(-1, "Invalid id to update status. " . $id)) {
                return json_encode(array("result" => 1, "log_result" => 0));
            }
            return json_encode(array("result" => 1, "log_result" => 1));
        }
    }

    public function getCurrentStudent()
    {
        $sql = "SELECT id FROM admission WHERE status='waiting' ORDER BY rank";
        $result = mysqli_query($this->_connection, $sql);
        $row = mysqli_fetch_row($result);
        $id = $row["id"];
        return $this->getStudentById($id);
    }

    public function getNextStudent($id)
    {
        $sql = "SELECT rank FROM admission WHERE id=".$id;
        $result = mysqli_query($this->_connection, $sql);
        $row = mysqli_fetch_row($result);
        $rank = $row["rank"];
        $rank++;
        return $this->getStudentByRank($rank);
    }

    public function getPreviousStudent($id)
    {
        $sql = "SELECT rank FROM admission WHERE id=".$id;
        $result = mysqli_query($this->_connection, $sql);
        $row = mysqli_fetch_row($result);
        $rank = $row["rank"];
        $rank--;
        return $this->getStudentByRank($rank);
    }

    public function getSeat()
    {
        $sqlADME = "SELECT * FROM admission WHERE major='adme'";
        $sqlAERO = "SELECT * FROM admission WHERE major='aero'";
        $sqlICE = "SELECT * FROM admission WHERE major='ice'";
        $sqlNANO = "SELECT * FROM admission WHERE major='nano'";

        $admeResult = mysqli_query($this->_connection, $sqlADME);
        $aeroResult = mysqli_query($this->_connection, $sqlAERO);
        $iceResult = mysqli_query($this->_connection, $sqlICE);
        $nanoResult = mysqli_query($this->_connection, $sqlNANO);

        if ($admeResult && $aeroResult && $iceResult && $nanoResult) {
            $adme = $this->MAX_ADME - mysqli_num_rows($admeResult);
            $aero = $this->MAX_AERO - mysqli_num_rows($aeroResult);
            $ice = $this->MAX_ICE - mysqli_num_rows($iceResult);
            $nano = $this->MAX_NANO - mysqli_num_rows($nanoResult);
            if ($this->updateLog(0, "Got remaining seat")) {
                return json_encode(array("result" => 0, "log_result" => 0, "admeNum" => $adme, "aeroNum" => $aero, "iceNum" => $ice, "nanoNum" => $nano));
            }
            return json_encode(array("result" => 0, "log_result" => 1, "admeNum" => $adme, "aeroNum" => $aero, "iceNum" => $ice, "nanoNum" => $nano));
        } else {
            if ($this->updateLog(0, "Failed to get remaining seat.")) {
                return json_encode(array("result" => 1, "log_result" => 0, "admeNum" => null, "aeroNum" => null, "iceNum" => null, "nanoNum" => null));
            }
            return json_encode(array("result" => 1, "log_result" => 1, "admeNum" => null, "aeroNum" => null, "iceNum" => null, "nanoNum" => null));
        }
    }


}