<?php

namespace MyApp\Controller;
use MyApp\Logger\LoggerInterface;
use PDO;



abstract class DBClass
{
    public $name;
    protected PDO $dbh;
    protected LoggerInterface $logger;
    protected $template;
    protected $template1;

    public function __construct( PDO $dbh, LoggerInterface $logger)
    {
        // need to implement constructor
        $this->dbh = $dbh;
        $this->logger = $logger;
    }
    protected function auth($login, $password, $i_login, $i_password){
        if ($login == $i_login && $password == $i_password){
            $_SESSION["is_auth"] = true;
            $_SESSION["login"] = $i_login;
            return true;
        }
        else{
            $_SESSION["is_auth"] = false;
            return false;
        }
    }

    public function isAuth(){
        if (isset($_SESSION["is_auth"])){
            return $_SESSION["is_auth"];
        }
        else return false;
    }

    public function get_data( $data){
        $query = "SELECT * FROM `users` WHERE login = ?";
        return $this->queryFetchAll( $query, $data);
    }


    protected function queryFetchAll( $query, $queryParams ) {
        $this->logger->logEvent("query: ".$query, __FILE__, __LINE__, __FUNCTION__);
        $this->logger->logEvent("params: ".var_export($queryParams, true), __FILE__, __LINE__, __FUNCTION__);
        // подготовка запроса
        $sth = $this->dbh->prepare( $query );
        // выполнение запроса
        $sth->execute( $queryParams );
        #var_dump($sth->fetchAll());
        return $sth->fetchAll();
    }
}