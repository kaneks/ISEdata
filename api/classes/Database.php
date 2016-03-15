<?php

/**
 * Created by PhpStorm.
 * User: Kaneks
 * Date: 3/8/2016 AD
 * Time: 10:33 PM
 */
class Database
{
    protected $_connection;
    private static $_instance; //The single instance
    private $_host = "localhost";
    private $_username = "ise";
    private $_password = "qrAtqjWmLPB9EAPQ";
    private $_database = "ise_admission";

    /*
    Get an instance of the Database
    @return Instance
    */
    public static function getInstance()
    {
        if (!self::$_instance) { // If no instance then make one
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    // Constructor
    public function __construct()
    {
        $this->_connection = new mysqli($this->_host, $this->_username,
            $this->_password, $this->_database);

        // Error handling
        if (mysqli_connect_error()) {
            trigger_error("Failed to connect to MySQL: " . mysqli_connect_error(),
                E_USER_ERROR);
        }
    }

    // Magic method clone is empty to prevent duplication of connection
    private function __clone()
    {
    }

    // Get mysqli connection
    public function getConnection()
    {
        return $this->_connection;
    }
}
