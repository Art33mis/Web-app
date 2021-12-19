<?php

namespace MyApp\Controller;
use Twig\Evironment;
require '/Users/artyom_avtaykin/PhpstormProjects/avtajkin/finalproject1/vendor/autoload.php';

class ItemClass extends DBClass
{
    public $name = "CatalogClass";
    protected $template;

    protected $resultId = 0;
    private CatalogClass $catalogs;
    protected $resultQuery = [];
    protected $paramsQuery = [];
    protected $errorsQuery = [];

    protected $errorNotFound = ['description' => "По вашему запросу ничего не найдено"];
    protected $errorIncorrect = ['description' => "Неверные данные"];

    public function orderinfo( $listPar ) {
        $this->logger->logEvent('inside orderinfo', __FILE__, __LINE__, __FUNCTION__);
        $queryOrder = "SELECT `oir`.oid, `o`.order_num, `o`.order_date, client_id, CONCAT(`c`.`lastname`, ' ', `c`.`firstname`) as `name`,
            COUNT(*) as `count`, SUM(oir.quantity*catlog_item.price) as `sum`
            FROM `catlog_item`
            LEFT JOIN order_item_rel oir on catlog_item.item_id = oir.item_id
            LEFT JOIN `order` o on oir.oid = o.oid
            LEFT JOIN `client` c on o.client_id = c.ID
            WHERE o.oid = ?";

        $queryItems = "SELECT `oir`.item_id, `catlog_item`.item_name, `oir`.quantity, `catlog_item`.description,
            `catlog_item`.price
            FROM `catlog_item`
            LEFT JOIN order_item_rel oir on catlog_item.item_id = oir.item_id
            where oir.oid = ?";

        $dataOrder = $this->queryFetchAll($queryOrder, $listPar);
        $dataItems = $this->queryFetchAll($queryItems, $listPar);

        return ['dataorder' => $dataOrder,'dataitems' => $dataItems];
    }

    public function get_info($twig){
        if ($this->isAuth()) {
            $this->template = $twig->load('orderinfo' . '.html');
            $data = $this->get_data([$_SESSION['login']]);
            if (count($_GET) == 2 && array_key_exists('oid', $_GET)) {

                if (!empty($_GET["oid"]) && is_int((int)$_GET["oid"]) && $_GET["oid"] > 0) {
                    array_push($this->paramsQuery, (int)$_GET["oid"]);
                } else {
                    $this->errorsQuery[] = $this->errorNotFound;
                }

                if (empty($this->errorsQuery)) {

                    $this->resultQuery = $this->orderinfo($this->paramsQuery);

                }
            } else {
                $this->errorsQuery[] = $this->errorNotFound;
            }
            $this->resultQuery["errors"] = $this->errorsQuery;
            $this->resultQuery["resultId"] = $this->resultId;
            if ($this->resultQuery['dataorder'][0]['client_id'] == $data[0]['ID']) {
                echo $this->template->render($this->resultQuery);
            }
            else{

                echo "Неверное id заказа. Попробуйте ещё";
            }
        }else{
            $auth = new AuthClass($this->dbh, $this->logger);
            $auth->authorization($twig);
        }
    }



    public function addorderitem($listPar){

        $this->logger->logEvent('inside addorderitem', __FILE__, __LINE__, __FUNCTION__);
        $queryParams = $listPar;
        $queryParamsif = $listPar;
        unset($queryParamsif[2]);
        $queryif = "SELECT * FROM `order_item_rel` WHERE `oid` = ? and `item_id` = ?";
        $check = $this->queryFetchAll($queryif, $queryParamsif);
        if(!empty($check)){
            $queryParams[0] = (string)((int)$queryParams[2]+(int)$check[0]['quantity']);

            $queryParams[2] = $queryParamsif[0];
            $query = "UPDATE `order_item_rel` SET `quantity` = ? WHERE `item_id` = ? and `oid` = ?;";
        $this->queryFetchAll($query, $queryParams);
        }else{
            $query = "INSERT INTO `order_item_rel` (`oid`, `item_id`, `quantity`)
            VALUES (?, ?, ?);";
        $this->queryFetchAll($query, $queryParams);
        }

    }

    public function get_addorderitem($twig){
        if ($this->isAuth()) {
            $this->template = $twig->load('addorderitem' . '.html');
            if ($_GET["oid"] > 0) {
                $get = ['oid' => (int)$_GET["oid"]];
            } else {
                $get = ['oid' => (int)$_POST["oid"]];
            }
            if (count($get) == 1 && array_key_exists('oid', $get) && $get["oid"] > 0 && is_int($get["oid"])) {
                $this->catalogs = new CatalogClass($this->dbh, $this->logger);
                $this->resultQuery['catalog'] = $this->catalogs->listcatalog();
                $this->resultQuery['oid'] = $get['oid'];
                if (array_key_exists('quantity', $_POST) &&
                    array_key_exists('item_id', $_POST)) {
                    array_push($this->paramsQuery, $get["oid"]);
                    if (!empty($_POST["item_id"]) && $_POST["item_id"] != '0') {
                        array_push($this->paramsQuery, $_POST["item_id"]);
                    } else {
                        $this->errorsQuery[] = $this->errorNotFound;
                    }
                    if (!empty($_POST["quantity"])) {

                        array_push($this->paramsQuery, $_POST["quantity"]);

                    } else {
                        $this->errorsQuery[] = $this->errorNotFound;
                    }

                    if (empty($this->errorsQuery)) {
                        $this->addorderitem($this->paramsQuery);
                        $this->resultId = 1;
                    }

                } else if (!count($_POST) == 0) {
                    $this->errorsQuery[] = $this->errorIncorrect;
                }
            } else {
                $this->errorsQuery[] = $this->errorNotFound;
            }
            $this->resultQuery["errors"] = $this->errorsQuery;
            $this->resultQuery["resultId"] = $this->resultId;
            $data = $this->get_data([$_SESSION['login']]);
            if ((int)$data[0]['roleid'] != 1) {
                $info = $this->orderinfo([$this->resultQuery['oid']]);
                if ($info['dataorder'][0]['client_id'] == $data[0]['ID']) {
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

    public function checkdeleteorderitem($listPar){

        $this->logger->logEvent('inside checkdeleteorderitem', __FILE__, __LINE__, __FUNCTION__);
        $queryOrder = "SELECT *
            FROM `order_item_rel`
            LEFT JOIN catlog_item ON order_item_rel.item_id = catlog_item.item_id
            where `order_item_rel`.oid = ? and `order_item_rel`.item_id = ?";

        return $this->queryFetchAll($queryOrder, $listPar);

    }

    public function deleteorderitem($listPar){

        $this->logger->logEvent('inside deleteorderitem', __FILE__, __LINE__, __FUNCTION__);
        $query = "DELETE FROM `order_item_rel`
        WHERE `order_item_rel`.`oid` = ? and `order_item_rel`.`item_id` = ?;";
        $this->queryFetchAll($query, $listPar);

    }

    public function get_deleteorderitem($twig){
        if ($this->isAuth()) {
            $this->template = $twig->load('deleteorderitem' . '.html');
            if ($_GET["oid"] > 0 && $_GET["itemid"] > 0) {
                $get = ['oid' => (int)$_GET["oid"], 'itemid' => (int)$_GET["itemid"]];
            } else {
                $get = ['oid' => (int)$_POST["oid"], 'itemid' => (int)$_POST["itemid"]];
            }
            if (count($get) == 2 && array_key_exists('oid', $get) && $get["oid"] > 0 && is_int($get["oid"])
                && array_key_exists('itemid', $get) && $get["itemid"] > 0 && is_int($get["itemid"])) {
                if (!count($_POST) == 0 && !array_key_exists('del', $_POST)) {
                    array_push($this->paramsQuery, $get["oid"]);
                    array_push($this->paramsQuery, $get["itemid"]);
                    if ($this->checkdeleteorderitem($this->paramsQuery)) {
                        $this->deleteorderitem($this->paramsQuery);
                        $this->resultId = 1;
                    } else {
                        $this->errorsQuery[] = $this->errorNotFound;
                    }
                } else {
                    if (!empty($this->checkorderitem([$get["oid"], $get["itemid"]]))) {
                        $this->resultQuery["data"] = $this->checkorderitem([$get["oid"], $get["itemid"]]);
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
                $info = $this->orderinfo([$get['oid']]);
                if ($info['dataorder'][0]['client_id'] == $data[0]['ID']) {
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

    public function checkorderitem(  $listPar  ) {
        $this->logger->logEvent('inside checkorderitem', __FILE__, __LINE__, __FUNCTION__);
        $queryOrder = "SELECT *
            FROM `order_item_rel`
            LEFT JOIN catlog_item ON order_item_rel.item_id = catlog_item.item_id
            where `order_item_rel`.oid = ? and `order_item_rel`.item_id = ?";

        return $this->queryFetchAll($queryOrder, $listPar);
    }

    public function editorderitem($listPar ){

        $this->logger->logEvent('inside editorderitem', __FILE__, __LINE__, __FUNCTION__);
        $query = "UPDATE `order_item_rel` SET `quantity` = ? WHERE `order_item_rel`.`oid` = ? AND `order_item_rel`.`item_id` = ?;";
        $this->queryFetchAll($query, $listPar);

    }

    public function get_editorderitem($twig){
        if ($this->isAuth()) {
            $this->template = $twig->load('editorderitem' . '.html');
            if ($_GET["oid"] > 0 && $_GET["itemid"] > 0) {
                $get = ['oid' => (int)$_GET["oid"], 'itemid' => (int)$_GET["itemid"]];
            } else {
                $get = ['oid' => (int)$_POST["oid"], 'itemid' => (int)$_POST["itemid"]];
            }
            if (count($get) == 2 && array_key_exists('oid', $get) && $get["oid"] > 0 && is_int($get["oid"])
                && array_key_exists('itemid', $get) && $get["itemid"] > 0 && is_int($get["itemid"])) {

                if (!count($_POST) == 0 && !array_key_exists('modify', $_POST)) {

                    if (!empty($_POST["quantity"]) && is_int((int)$_POST["quantity"])) {
                        array_push($this->paramsQuery, (int)$_POST["quantity"]);
                    } else {
                        $this->errorsQuery[] = $this->errorNotFound;
                    }
                    array_push($this->paramsQuery, $get["oid"]);
                    array_push($this->paramsQuery, $get["itemid"]);
                    if (!empty($this->checkorderitem([$get["oid"], $get["itemid"]]))) {
                        $this->editorderitem($this->paramsQuery);
                        $this->resultId = 1;
                    } else {
                        $this->errorsQuery[] = $this->errorNotFound;
                    }
                } else {

                    if (!empty($this->checkorderitem([$get["oid"], $get["itemid"]]))) {
                        $this->resultQuery["data"] = $this->checkorderitem([$get["oid"], $get["itemid"]]);
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
                $info = $this->orderinfo([$get['oid']]);
                if ($info['dataorder'][0]['client_id'] == $data[0]['ID']) {
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


}
