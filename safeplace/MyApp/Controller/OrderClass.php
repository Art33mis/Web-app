<?php

namespace MyApp\Controller;
use Twig\Evironment;
require '/Users/artyom_avtaykin/PhpstormProjects/avtajkin/finalproject1/vendor/autoload.php';
class OrderClass extends DBClass
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


    public function listnames(){
        $this->logger->logEvent('inside listnames', __FILE__, __LINE__, __FUNCTION__);
        $data = $this->get_data([$_SESSION['login']]);
        if ((int)$data[0]['roleid'] == 1) {
            $query = "SELECT ID, CONCAT(`client`.`lastname`, ' ', `client`.`firstname`) as name
        FROM `client`";
            return $this->queryFetchAll($query, []);
        }
        else{
            $query = "SELECT ID, CONCAT(`client`.`lastname`, ' ', `client`.`firstname`) as name
        FROM `client` WHERE `client`.ID = ?";
            return $this->queryFetchAll($query, [(int)$data[0]['ID']]);
        }
    }

    public function list( $listPar, $filterParams = [] ) {
        $this->logger->logEvent('inside list', __FILE__, __LINE__, __FUNCTION__);
        $queryParams = $listPar;
        $query = "SELECT `order`.`oid`, `order`.`order_num`, `order`.`order_date`,
            CONCAT(`client`.`lastname`, ' ', `client`.`firstname`) as `name`,
            Count(*) as `count`, SUM(oir.quantity*catlog_item.price) as `sum`
            FROM `client`, `order`, `catlog_item`
            LEFT JOIN order_item_rel oir on catlog_item.item_id = oir.item_id
            WHERE oir.oid =`order`.oid and `client`.ID = `order`.client_id
            ";
        $data = $this->get_data([$_SESSION['login']]);
        if ((int)$data[0]['roleid'] == 1) {
            if (array_key_exists("client_id", $filterParams)) {
                $query = $query . " and  `order`.client_id = ?";
                $queryParams[] = $filterParams["client_id"];
            }
        }else{
            $query = $query . " and  `order`.client_id = ?";
            $queryParams[] = (int)$data[0]['ID'];
        }
        if( array_key_exists("order_num", $filterParams)){
            $query = $query." and `order`.`order_num` = ?";
            $queryParams[] = $filterParams["order_num"];
        }    
        if( array_key_exists("order_date", $filterParams)){
            $query = $query." and `order`.`order_date` = ?";
            $queryParams[] = $filterParams["order_date"];
        }

        $query .= " group by `order`.oid ";

        return ['names'=> $this->listnames() ,'data' => $this->queryFetchAll($query, $queryParams)];
    }


    public function get_list($twig){
        if ($this->isAuth()) {
            $this->template = $twig->load("listorders.html");
            if (count($_GET) == 1) {
                if (array_key_exists("client_id", $_POST) && $_POST["client_id"] > 0) {
                    if (!empty($_POST["client_id"]) && is_int((int)$_POST["client_id"])) {
                        $this->paramsFilters["client_id"] = $_POST["client_id"];
                    } else {
                        $this->errorsQuery[] = $this->errorNotFound;
                    }
                }
                if (array_key_exists("order_num", $_POST)) {
                    if (!empty($_POST["order_num"]) && is_string($_POST["order_num"])) {
                        $this->paramsFilters["order_num"] = $_POST["order_num"];
                    }
                }

                if (array_key_exists("order_date", $_POST)) {
                    if (!empty($_POST["order_date"])) {
                        $this->paramsFilters["order_date"] = $_POST["order_date"];
                    }
                }

                if (!empty($_POST["items_per_page"]) && array_key_exists("items_per_page", $_POST)) {
                    if ($_POST["items_per_page"] > 0) {
                        $this->paramsFilters["items_per_page"] = $_POST["items_per_page"];
                    } else {
                        $this->errorsQuery[] = $this->errorNotFound;
                    }
                } else {
                    $this->paramsFilters["items_per_page"] = 30;
                }

                if (!empty($_POST["page_number"]) && array_key_exists("page_number", $_POST)) {
                    if (is_int($_POST["page_number"]) && $_POST["page_number"] > 0) {
                        $this->paramsFilters["page_number"] = $_POST["page_number"];
                    } else {
                        $this->errorsQuery[] = $this->errorNotFound;

                    }
                } else {
                    $this->paramsFilters["page_number"] = 1;
                }

                if (empty($this->errorsQuery)) {

                    $this->resultQuery = $this->list($this->paramsQuery, $this->paramsFilters);

                }
            } else {
                $this->errorsQuery[] = $this->errorNotFound;
            }
            $this->resultQuery["errors"] = $this->errorsQuery;
            $this->resultQuery["resultId"] = $this->resultId;
            echo $this->template->render($this->resultQuery);
        }
        else{
            $auth = new AuthClass($this->dbh, $this->logger);
            $auth->authorization($twig);
        }
    }



    public function orderinfoById($listPar)
    {
        $this->logger->logEvent('inside orderinfoById', __FILE__, __LINE__, __FUNCTION__);
        $queryOrder = "SELECT *
            FROM `order`
            where `order`.oid = ?";
        return
                [
                    'data' => $this->queryFetchAll($queryOrder, $listPar),
                    'name' => $this->listnames()
                ];

    }



    public function addorder($listPar){
        $this->logger->logEvent('inside addorder', __FILE__, __LINE__, __FUNCTION__);
        $query = "INSERT INTO `order` (`order_num`, `order_date`, `client_id`) VALUES (?, ?, ?);";
        $this->queryFetchAll($query, $listPar);
        return 1;
    }

    public function get_addorder($twig){
        if ($this->isAuth()) {
            $this->template = $twig->load('addorder' . '.html');
            $this->resultQuery['names'] = $this->listnames();
            if (array_key_exists('ordernum', $_POST) &&
                array_key_exists('order_date', $_POST) && array_key_exists('client', $_POST)) {
                var_dump($_POST);
                if (!empty($_POST["ordernum"])) {

                    array_push($this->paramsQuery, $_POST["ordernum"]);

                } else {
                    $this->errorsQuery[] = $this->errorNotFound;
                }

                if (!empty($_POST["order_date"])) {
                    array_push($this->paramsQuery, $_POST["order_date"]);
                } else {
                    $this->errorsQuery[] = $this->errorNotFound;
                }

                if (!empty($_POST["client"]) && $_POST["client"] != '0') {
                    array_push($this->paramsQuery, $_POST["client"]);
                } else {
                    $this->errorsQuery[] = $this->errorNotFound;
                }

                if (empty($this->errorsQuery)) {

                    $this->resultId = $this->addorder($this->paramsQuery);
                }
            } else if (!count($_POST) == 0) {
                $this->errorsQuery[] = $this->errorIncorrect;
            }
            $this->resultQuery["errors"] = $this->errorsQuery;
            $this->resultQuery["resultId"] = $this->resultId;
            echo $this->template->render($this->resultQuery);
        }else{
            $auth = new AuthClass($this->dbh, $this->logger);
            $auth->authorization($twig);
        }
    }


    public function editorder($listPar){
        $this->logger->logEvent('inside editorder', __FILE__, __LINE__, __FUNCTION__);
        $query = "UPDATE `order` SET `order_num` = ?, `order_date` = ?, `client_id` = ? WHERE `order`.oid = ?;";
        $this->queryFetchAll($query, $listPar);
    }

    public function get_edit($twig){
        if ($this->isAuth()) {
            $this->template = $twig->load('editorder' . '.html');
            if ($_GET["oid"] > 0) {
                $get = ['oid' => (int)$_GET["oid"]];
            } else {
                $get = ['oid' => (int)$_POST["oid"]];
            }
            if (count($get) == 1 && array_key_exists('oid', $get) && $get["oid"] > 0 && is_int($get["oid"])) {
                if (!count($_POST) == 0 && !array_key_exists('modify', $_POST)) {
                    if (!empty($_POST['order_num']) && is_string($_POST['order_num'])) {
                        array_push($this->paramsQuery, $_POST['order_num']);
                    } else {
                        $this->errorsQuery[] = $this->errorNotFound;
                    }

                    if (!empty($_POST['order_date'])) {
                        array_push($this->paramsQuery, $_POST['order_date']);
                    } else {
                        $this->errorsQuery[] = $this->errorNotFound;
                    }

                    if (!empty($_POST["client"]) && is_int((int)$_POST["client"])) {
                        $cl_id = (int)$_POST["client"];
                        array_push($this->paramsQuery, $cl_id);
                    } else {
                        $this->errorsQuery[] = $this->errorNotFound;
                    }
                    array_push($this->paramsQuery, $get["oid"]);

                    if (!empty($this->orderinfoById([$get["oid"]]))) {
                        $this->editorder($this->paramsQuery);
                        $this->resultId = $this->paramsQuery[3];
                    } else {
                        $this->errorsQuery[] = $this->errorNotFound;
                    }
                } else {
                    $this->resultQuery['names'] = $this->listnames();
                    if (!empty($this->orderinfoById([$get["oid"]]))) {
                        $this->resultQuery['data'] = $this->orderinfoById([$get["oid"]]);
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
                if ($this->resultQuery['names'][0]['ID'] == $this->resultQuery['data']['data'][0]['client_id']) {
                    echo $this->template->render($this->resultQuery);
                } else {
                    echo "Неверное id, попробуйте еще";
                }
            }else{
                echo $this->template->render($this->resultQuery);
            }
        }else{
            $auth = new AuthClass($this->dbh, $this->logger);
            $auth->authorization($twig);
        }
    }

    public function checkdeleteorder($listPar){

        $this->logger->logEvent('inside checkdeleteorder', __FILE__, __LINE__, __FUNCTION__);

        return
            empty(
                $this->queryFetchAll(
                "SELECT * FROM `order_item_rel` WHERE `order_item_rel`.oid = ?",
                       $listPar)
                )
            &&
            !empty(
                $this->queryFetchAll(
                    "SELECT * FROM `order` WHERE `order`.oid = ?",
                          $listPar)
                );

    }

    public function deleteorder($listPar ){
        $this->logger->logEvent('inside deleteorder', __FILE__, __LINE__, __FUNCTION__);
        $query = "DELETE FROM `order` WHERE `order`.oid = ?";
        $this->queryFetchAll($query, $listPar);
    }

    public function get_delete($twig){
        if ($this->isAuth()) {
            $this->template = $twig->load('deleteorder' . '.html');
            if ($_GET["oid"] > 0) {
                $get = ['oid' => (int)$_GET["oid"]];
            } else {
                $get = ['oid' => (int)$_POST["oid"]];
            }
            var_dump($_POST);
            if (count($get) == 1 && array_key_exists('oid', $get) && $get["oid"] > 0 && is_int($get["oid"])) {
                if (!count($_POST) == 0 && !array_key_exists('del', $_POST)) {
                    array_push($this->paramsQuery, $get["oid"]);
                    if ($this->checkdeleteorder($this->paramsQuery)) {
                        $this->deleteorder($this->paramsQuery);
                        $this->resultId = 1;
                    } else {
                        $this->errorsQuery[] = $this->errorNotFound;
                    }
                } else {
                    $this->resultQuery['names'] = $this->listnames();
                    if (!empty($this->orderinfoById([$get["oid"]]))) {
                        $this->resultQuery['data'] = $this->orderinfoById([$get["oid"]]);

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
                if ($this->resultQuery['names'][0]['ID'] == $this->resultQuery['data']['data'][0]['client_id']) {
                    echo $this->template->render($this->resultQuery);
                } else {
                    echo "Неверное id, попробуйте еще";
                }
            }else{
                echo $this->template->render($this->resultQuery);
            }
        }else{
            $auth = new AuthClass($this->dbh, $this->logger);
            $auth->authorization($twig);
        }
    }


}

