<?php

namespace MyApp\Controller;
use Twig\Evironment;
require '/Users/artyom_avtaykin/PhpstormProjects/avtajkin/finalproject1/vendor/autoload.php';

class CatalogClass extends DBClass
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

    public function listcatalogbyID($itemid){
        $this->logger->logEvent('inside listcatalogbyname', __FILE__, __LINE__, __FUNCTION__);
        $query = "SELECT `item_name`, `description`, `price`, `item_id`
        FROM `catlog_item` WHERE item_id = ?";
        return $this->queryFetchAll($query, $itemid);
    }


    public function listcatalogbyname($name){
        $this->logger->logEvent('inside listcatalogbyname', __FILE__, __LINE__, __FUNCTION__);
        $query = "SELECT `item_name`, `item_id`
        FROM `catlog_item` WHERE item_name = ?";
        return $this->queryFetchAll($query, $name);
    }

    public function listcatalog(){
        $this->logger->logEvent('inside listcatalog', __FILE__, __LINE__, __FUNCTION__);
        $query = "SELECT `item_name`, `item_id` FROM `catlog_item`";
        return $this->queryFetchAll($query, []);
    }


    public function catalog( $listPar, $filterParams = []){
        $this->logger->logEvent('inside catalog', __FILE__, __LINE__, __FUNCTION__);
        $queryParams = $listPar;
        $query = "SELECT `catlog_item`.item_id, `catlog_item`.item_name, `catlog_item`.description,
            `catlog_item`.price, COUNT(*) as count
            FROM `catlog_item`
            LEFT JOIN order_item_rel oir on catlog_item.item_id = oir.item_id WHERE 1=1";
        if( array_key_exists("item_name", $filterParams)){
            $query = $query." and `catlog_item`.item_name = ?";
            array_push($queryParams,$filterParams["item_name"]);
        }
        if( array_key_exists("description", $filterParams)){
            $query = $query." and `catlog_item`.description = ?";
            array_push($queryParams,$filterParams["description"]);
        }
        if( array_key_exists("minprice", $filterParams)){
            $query = $query." and `catlog_item`.price >= ?";
            array_push($queryParams,$filterParams["minprice"]);
        }
        if( array_key_exists("maxprice", $filterParams)){
            $query = $query." and `catlog_item`.price <= ?";
            array_push($queryParams,$filterParams["maxprice"]);
        }
        $query = $query." group by `catlog_item`.`item_id`";

        return ['data' => $this->queryFetchAll($query, $queryParams)];
    }

    public function get_catalog($twig)
    {
            if (count($_GET) == 1) {
                if (!empty($_POST["item_name"]) && array_key_exists("item_name", $_POST)) {
                    if (is_string($_POST["item_name"])) {
                        $this->paramsFilters["item_name"] = $_POST["item_name"];
                    } else {
                        $this->errorsQuery[] = $this->errorNotFound;
                    }
                }

                if (!empty($_POST["description"]) && array_key_exists("description", $_POST)) {
                    if (is_string($_POST["description"])) {
                        $this->paramsFilters["description"] = $_POST["description"];
                    } else {
                        $this->errorsQuery[] = $this->errorNotFound;
                    }
                }

                if (!empty($_POST["minprice"]) && array_key_exists("minprice", $_POST)) {

                    $this->paramsFilters["minprice"] = $_POST["minprice"];
                }

                if (!empty($_POST["maxprice"]) && array_key_exists("maxprice", $_POST)) {

                    $this->paramsFilters["maxprice"] = $_POST["maxprice"];

                }

                if (empty($this->errorsQuery)) {

                    $this->resultQuery = $this->catalog($this->paramsQuery, $this->paramsFilters);
                }

            } else {
                $this->errorsQuery[] = $this->errorNotFound;
            }
            $this->resultQuery["errors"] = $this->errorsQuery;
            $this->resultQuery["resultId"] = $this->resultId;
            if ($this->isAuth()) {
                $data = $this->get_data([$_SESSION['login']]);
                if ($data[0]['roleid'] == 1) {
                    $this->template = $twig->load('catalog' . '.html');
                    echo $this->template->render($this->resultQuery);
                }else {
                    $this->template = $twig->load('catalog3' . '.html');
                    echo $this->template->render($this->resultQuery);
                }

                }else{
                $this->template = $twig->load('catalog3' . '.html');
                echo $this->template->render($this->resultQuery);
            }

    }

    public function addcatalogitem($listPar){

        $this->logger->logEvent('inside addcatalogitem', __FILE__, __LINE__, __FUNCTION__);
        $query = " INSERT INTO `catlog_item` (`item_name`, `description`, `price`)
            VALUES (?, ?, ?);";
        $this->queryFetchAll($query, $listPar);
    }

    public function get_addcatalogitem($twig){
        if ($this->isAuth()) {
            $data = $this->get_data([$_SESSION['login']]);
            if ((int)$data[0]['roleid'] == 1) {
            $this->template = $twig->load('addcatalogitem' . '.html');
            if (array_key_exists('item_name', $_POST) &&
                array_key_exists('description', $_POST) &&
                array_key_exists('price', $_POST)) {

                if (!empty($_POST["item_name"]) && is_null($this->listcatalogbyname([$_POST["item_name"]])[0])) {
                    array_push($this->paramsQuery, $_POST["item_name"]);
                } else {
                    $this->errorsQuery[] = $this->errorNotFound;
                }

                if (!empty($_POST["description"])) {
                    array_push($this->paramsQuery, $_POST["description"]);
                } else {
                    $this->errorsQuery[] = $this->errorNotFound;
                }

                if (!empty($_POST["price"])) {
                    array_push($this->paramsQuery, (int)$_POST["price"]);
                } else {
                    $this->errorsQuery[] = $this->errorNotFound;
                }


                if (empty($this->errorsQuery)) {

                    $this->addcatalogitem($this->paramsQuery);
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

    public function editcatalogitem($listPar ){
        $this->logger->logEvent('inside editcatalogitem', __FILE__, __LINE__, __FUNCTION__);
        $query = "UPDATE `catlog_item` SET `item_name` = ?, `description` = ?, `price`=? WHERE `item_id` = ?;";
        $this->queryFetchAll($query, $listPar);
    }

    public function checkeditcatalogitem(  $listPar  ) {
        $this->logger->logEvent('inside checkeditcatalogitem', __FILE__, __LINE__, __FUNCTION__);
        $queryOrder = "SELECT *
            FROM `catlog_item`
            where `catlog_item`.item_id = ? ";

        return $this->queryFetchAll($queryOrder, $listPar);
    }

    public function get_editcatalogitem($twig){
        if ($this->isAuth()) {
            $data = $this->get_data([$_SESSION['login']]);
            if ((int)$data[0]['roleid'] == 1) {
            $this->template = $twig->load('editcatalogitem' . '.html');
            if ($_GET["oid"] > 0) {
                $get = ['oid' => (int)$_GET["oid"]];
            } else {
                $get = ['oid' => (int)$_POST["oid"]];
            }
            if (count($get) == 1 && array_key_exists('oid', $get) && $get["oid"] > 0 && is_int($get["oid"])) {
                if (!count($_POST) == 0 && !array_key_exists('modify', $_POST)) {
                    if (!empty($_POST["item_name"])) {
                        array_push($this->paramsQuery, $_POST["item_name"]);

                    } else {
                        $this->errorsQuery[] = $this->errorNotFound;
                    }
                    if (!empty($_POST["description"])) {
                        array_push($this->paramsQuery, $_POST["description"]);
                    } else {
                        $this->errorsQuery[] = $this->errorNotFound;
                    }

                    if (!empty($_POST["price"]) && is_int((int)$_POST["price"]) && $_POST["price"] > 0) {

                        array_push($this->paramsQuery, (int)$_POST["price"]);
                    } else {
                        $this->errorsQuery[] = $this->errorNotFound;
                    }
                    array_push($this->paramsQuery, $get["oid"]);
                    if (!empty($this->checkeditcatalogitem([$get["oid"]]))) {
                        $this->editcatalogitem($this->paramsQuery);
                        $this->resultId = 1;

                    } else {
                        $this->errorsQuery[] = $this->errorNotFound;
                    }
                } else {
                    if (!empty($this->checkeditcatalogitem([$get["oid"]]))) {
                        $this->resultQuery["data"] = $this->checkeditcatalogitem([$get["oid"]]);
                        var_dump($this->resultQuery);
                    } else {
                        var_dump($get);
                        $this->errorsQuery[] = $this->errorNotFound;
                    }
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

    public function checkdeletecatalogitem($listPar){

        $this->logger->logEvent('inside checkdeletecatalogitem', __FILE__, __LINE__, __FUNCTION__);
        $query = "SELECT *
            FROM `catlog_item`
            where `catlog_item`.item_id = ?";

        $query2 = "SELECT *
            FROM `order_item_rel`
            where `order_item_rel`.item_id = ?";

        return !empty($this->queryFetchAll($query, $listPar))&&empty($this->queryFetchAll($query2, $listPar));

    }

    public function deletecatalogitem($listPar ){
        $this->logger->logEvent('inside deletecatalogitem', __FILE__, __LINE__, __FUNCTION__);
        $query = "DELETE FROM `catlog_item` WHERE `item_id`=?;";
        $this->queryFetchAll($query, $listPar);
    }

    public function get_deletecatalogitem($twig){
        if ($this->isAuth()) {
            $data = $this->get_data([$_SESSION['login']]);
            if ((int)$data[0]['roleid'] == 1) {
            $this->template = $twig->load('deletecatalogitem' . '.html');
            if ($_GET["itemid"] > 0) {
                $get = ['oid' => (int)$_GET["itemid"]];
            } else {
                $get = ['oid' => (int)$_POST["itemid"]];
            }
            if (count($get) == 1 && array_key_exists('oid', $get) && $get["oid"] > 0 && is_int($get["oid"])) {
                if (!count($_POST) == 0 && !array_key_exists('del', $_POST)) {

                    array_push($this->paramsQuery, $get["oid"]);

                    if ($this->checkdeletecatalogitem($this->paramsQuery)) {
                        $this->deletecatalogitem($this->paramsQuery);
                        $this->resultId = 1;
                    } else {
                        $this->errorsQuery[] = $this->errorNotFound;
                    }
                } else {
                    $this->resultQuery['names'] = $this->listcatalog();
                    if (!empty($this->listcatalogbyID([$get["oid"]]))) {
                        $this->resultQuery['data'] = $this->listcatalogbyID([$get["oid"]]);

                    } else {
                        $this->errorsQuery[] = $this->errorNotFound;
                    }
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

}
