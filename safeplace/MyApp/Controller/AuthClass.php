<?php


namespace MyApp\Controller;


class AuthClass extends DBClass
{
    public $name = "CatalogClass";
    public function get_permission($page){
        $query = "SELECT * FROM `permissions` WHERE permissions.page = ?";
        return $this->queryFetchAll( $query, $page);
    }
    public function authorization($twig){
        $this->template = $twig->load('auth'.'.html');
        $this->template1 = $twig->load('menu'.'.html');
        if (count($_POST)>0){
            if (!$this->isAuth()){
                $data = $this->get_data([$_POST['login']]);
                $this->auth($data[0]['login'], $data[0]['pwd_hash'], $_POST['login'], hash('sha512',$_POST['login'].$_POST['password']));
                echo $this->template->render();
            }
            else {
                echo $this->template1->render();
            }

        }else{
            echo $this->template->render();
        }

    }
}