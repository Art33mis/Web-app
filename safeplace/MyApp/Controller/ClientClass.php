<?php

namespace MyApp\Controller;
use Twig\Evironment;
require '/Users/artyom_avtaykin/PhpstormProjects/avtajkin/finalproject1/vendor/autoload.php';

class ClientClass extends DBClass
{
    public $name = "CatalogClass";
    protected $template;

    protected $resultId = 0;

    protected $resultQuery = [];
    protected $paramsFilters = [];
    protected $paramsQuery = [];
    protected $errorsQuery = [];

    protected $errorNotFound = ['description' => "По вашему запросу ничего не найдено"];
    protected $errorIncorrect = ['description' => "Неверные данные"];

    public function listnames($ID){
        $this->logger->logEvent('inside listnames', __FILE__, __LINE__, __FUNCTION__);
        $query = "SELECT `ID`, `lastname`,  `firstname`, `birthdate`, `address` 
        FROM `client` WHERE ID = ?";
        return $this->queryFetchAll($query, $ID);
    }


    public function listclients($listPar ,$filterParams = []) {
        $this->logger->logEvent('inside listclients', __FILE__, __LINE__, __FUNCTION__);
        $queryParams = $listPar;
        $query = "SELECT `client`.ID, `client`.lastname, `client`.firstname, `client`.birthdate,
            `client`.address, COUNT(`oir`.oid) as count,
            SUM(`ci`.price * `oir`.quantity) as total_sum
            FROM `client`
            LEFT JOIN `order` o on client.ID = o.client_id
            LEFT JOIN `order_item_rel` oir on o.oid = oir.oid
            LEFT JOIN `catlog_item` ci on oir.item_id = ci.item_id
            WHERE 1=1 ";
        if( array_key_exists("lastname", $filterParams)){
            $query = $query." and `client`.lastname = ?";
            array_push($queryParams,$filterParams["lastname"]);
        }

        if( array_key_exists("birthyear", $filterParams)){
            $query = $query." and YEAR(`client`.birthdate) = ?";
            array_push($queryParams,$filterParams["birthyear"]);
        }
        $query .= " group by ID";
        $data = $this->queryFetchAll($query, $queryParams);
        return ['data' => $data];
    }

    public function get_clients($twig){
        if ($this->isAuth()) {
            $data = $this->get_data([$_SESSION['login']]);
            if ($data[0]['roleid'] == 1) {
                $this->template = $twig->load('listclients' . '.html');
                if (count($_GET) == 1) {

                    if (!empty($_POST["lastname"]) && array_key_exists("lastname", $_POST)) {
                        if (is_string($_POST["lastname"])) {
                            $this->paramsFilters["lastname"] = $_POST["lastname"];
                        } else {
                            $this->errorsQuery[] = $this->errorNotFound;
                        }
                    }

                    if (!empty($_POST["birthyear"]) && array_key_exists("birthyear", $_POST)) {
                        if (is_string($_POST["birthyear"])) {
                            $this->paramsFilters["birthyear"] = $_POST["birthyear"];
                        } else {
                            $this->errorsQuery[] = $this->errorNotFound;
                        }
                    }


                    if (empty($this->errorsQuery)) {

                        $this->resultQuery = $this->listclients($this->paramsQuery, $this->paramsFilters);
                    }
                } else {
                    $this->errorsQuery[] = $this->errorNotFound;
                }
                $this->resultQuery["errors"] = $this->errorsQuery;
                $this->resultQuery["resultId"] = $this->resultId;
                echo $this->template->render($this->resultQuery);
            }else{
                $this->template = $twig->load('menu' . '.html');
                echo $this->template->render();
            }
        }else{
            $auth = new AuthClass($this->dbh, $this->logger);
            $auth->authorization($twig);
        }
    }


    public function checkclient(  $listPar  ) {
        $this->logger->logEvent('inside checkclient', __FILE__, __LINE__, __FUNCTION__);
        $queryOrder = "SELECT *
            FROM `client`
            where `client`.ID = ?";

        return $this->queryFetchAll($queryOrder, $listPar);
    }



    public function addclient($listPar ){
         $this->logger->logEvent('inside addclient', __FILE__, __LINE__, __FUNCTION__);
        $query = "INSERT INTO `client` (`lastname`, `firstname`, `birthdate`,`address`)
                VALUES (?, ?, ?, ?);";
        $this->queryFetchAll($query, $listPar);
    }

    public function get_addclient($twig){
        if ($this->isAuth()) {
            $data = $this->get_data([$_SESSION['login']]);
            if ((int)$data[0]['roleid'] == 1) {
                $this->template = $twig->load('addclient' . '.html');

                if (array_key_exists('lastname', $_POST) &&
                    array_key_exists('firstname', $_POST) &&
                    array_key_exists('birthdate', $_POST) &&
                    array_key_exists('address', $_POST)) {

                    if (!empty($_POST["lastname"])) {
                        array_push($this->paramsQuery, $_POST["lastname"]);
                    } else {
                        $this->errorsQuery[] = $this->errorNotFound;
                    }

                    if (!empty($_POST["firstname"])) {
                        array_push($this->paramsQuery, $_POST["firstname"]);
                    } else {
                        $this->errorsQuery[] = $this->errorNotFound;
                    }

                    if (!empty($_POST["birthdate"])) {
                        array_push($this->paramsQuery, $_POST["birthdate"]);
                    } else {
                        $this->errorsQuery[] = $this->errorNotFound;
                    }

                    array_push($this->paramsQuery, $_POST["address"]);

                    var_dump($this->paramsQuery);

                    if (empty($this->errorsQuery)) {

                        $this->addclient($this->paramsQuery);
                        $this->resultId = 1;
                    }

                } else if (!count($_POST) == 0) {
                    $this->errorsQuery[] = $this->errorIncorrect;
                }
                $this->resultQuery["errors"] = $this->errorsQuery;
                $this->resultQuery["resultId"] = $this->resultId;
                echo $this->template->render($this->resultQuery);
            }else{
                $this->template = $twig->load('menu' . '.html');
                echo $this->template->render();
            }
        }else{
            $auth = new AuthClass($this->dbh, $this->logger);
            $auth->authorization($twig);
        }
    }

    public function editclient($listPar ){
        $this->logger->logEvent('inside editclient', __FILE__, __LINE__, __FUNCTION__);
        $query = "UPDATE `client` SET `firstname` = ? ,`lastname`=?,`birthdate`=?, `address`=? WHERE `ID`=?";
        $this->queryFetchAll($query, $listPar);


    }

    public function get_editclient($twig){
        if ($this->isAuth()) {
            $this->template = $twig->load('editclient' . '.html');
            if ($_GET["clientid"] > 0) {
                $get = ['oid' => (int)$_GET["clientid"]];
            } else {
                $get = ['oid' => (int)$_POST["clientid"]];
            }
            if (count($get) == 1 && array_key_exists('oid', $get) && $get["oid"] > 0 && is_int($get["oid"])) {
                if (!count($_POST) == 0 && !array_key_exists('modify', $_POST)) {
                    if (!empty($_POST["firstname"])) {
                        array_push($this->paramsQuery, $_POST["firstname"]);
                    } else {
                        $this->errorsQuery[] = $this->errorNotFound;
                    }
                    if (!empty($_POST["lastname"])) {
                        array_push($this->paramsQuery, $_POST["lastname"]);
                    } else {
                        $this->errorsQuery[] = $this->errorNotFound;
                    }

                    if (!empty($_POST["birthdate"])) {
                        array_push($this->paramsQuery, $_POST["birthdate"]);
                    } else {
                        $this->errorsQuery[] = $this->errorNotFound;
                    }
                    if (!empty($_POST["address"])) {
                        array_push($this->paramsQuery, $_POST["address"]);
                    } else {
                        $this->errorsQuery[] = $this->errorNotFound;
                    }

                    array_push($this->paramsQuery, $get["oid"]);
                    if (!empty($this->checkclient([$get["oid"]]))) {
                        $this->editclient($this->paramsQuery);
                        $this->resultId = 1;

                    } else {
                        $this->errorsQuery[] = $this->errorNotFound;
                    }
                } else {
                    if (!empty($this->checkclient([$get["oid"]]))) {
                        $this->resultQuery["data"] = $this->checkclient([$get["oid"]]);
                    } else {
                        $this->errorsQuery[] = $this->errorNotFound;
                    }
                }
            } else {
                $this->errorsQuery[] = $this->errorNotFound;
            }
            $this->resultQuery["errors"] = $this->errorsQuery;
            $this->resultQuery["resultId"] = $this->resultId;
            $data = $this->get_data([$_SESSION['login']]);
            if ((int)$data[0]['roleid'] != 1) {
                if ($get['oid'] == $data[0]['ID']) {
                    echo $this->template->render($this->resultQuery);
                } else {
                    echo "Неверный id. Попробуйте ещё";
                }
            }else{
                echo $this->template->render($this->resultQuery);
            }
        }else{
            $auth = new AuthClass($this->dbh, $this->logger);
            $auth->authorization($twig);
        }
    }

    public function checkdeleteclient($listPar){

        $this->logger->logEvent('inside checkdeleteclient', __FILE__, __LINE__, __FUNCTION__);
        $query = "SELECT *
            FROM `client`
            where `client`.ID = ?";

        $query2 = "SELECT *
            FROM `order`
            where `order`.client_id = ?";

        return !empty($this->queryFetchAll($query, $listPar))&&empty($this->queryFetchAll($query2, $listPar));

    }

    public function deleteclient($listPar){
         $this->logger->logEvent('inside deleteclient', __FILE__, __LINE__, __FUNCTION__);
         $query = "DELETE FROM `client` WHERE `ID` = ?;";
         $this->queryFetchAll($query, $listPar);

    }

    public function get_deleteclient($twig)
    {
        if ($this->isAuth()) {
            $data = $this->get_data([$_SESSION['login']]);
            if ((int)$data[0]['roleid'] == 1) {
            $this->template = $twig->load('deleteclient' . '.html');
            if ($_GET["clientid"] > 0) {
                $get = ['oid' => (int)$_GET["clientid"]];
            } else {
                $get = ['oid' => (int)$_POST["clientid"]];
            }
            if (count($get) == 1 && array_key_exists('oid', $get) && $get["oid"] > 0 && is_int($get["oid"])) {
                if (!count($_POST) == 0 && !array_key_exists('del', $_POST)) {

                    array_push($this->paramsQuery, $get["oid"]);

                    if ($this->checkdeleteclient($this->paramsQuery)) {
                        $this->deleteclient($this->paramsQuery);
                        $this->resultId = 1;
                    } else {
                        $this->errorsQuery[] = $this->errorNotFound;
                    }
                } else {
                    if (!empty($this->listnames([$get["oid"]]))) {
                        $this->resultQuery['data'] = $this->listnames([$get["oid"]]);

                    } else {
                        $this->errorsQuery[] = $this->errorNotFound;
                    }
                }
            } else {
                $this->errorsQuery[] = $this->errorNotFound;
            }
            $this->resultQuery["errors"] = $this->errorsQuery;
            $this->resultQuery["resultId"] = $this->resultId;
            var_dump($this->resultQuery);
            echo $this->template->render($this->resultQuery);
            }else{
                $this->template = $twig->load('menu' . '.html');
                echo $this->template->render();
            }
        }else{
            $auth = new AuthClass($this->dbh, $this->logger);
            $auth->authorization($twig);
        }

    }

}
