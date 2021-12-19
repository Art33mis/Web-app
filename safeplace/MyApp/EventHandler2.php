<?php

namespace MyApp;
use Twig\Evironment;
use MyApp\Logger\LoggerInterface;
use PDO;
use Exception;

require '/Users/artyom_avtaykin/PhpstormProjects/avtajkin/finalproject1/vendor/autoload.php';
class EventHandler2
{
    private string $page;
    private PDO $dbh;
    protected LoggerInterface $logger;
    protected array $handler;

    public function __construct(array $dbSettings, LoggerInterface $logger)
    {
        $options = [
            'debug' => false,
            'cache' => false,
            'c' => 'html'
        ];
        $this->loader = new \Twig\Loader\FilesystemLoader('../safeplace/MyApp/Templates');
        $this->twig = new \Twig\Environment($this->loader, $options);
        $this->logger = $logger;
        $page = array_key_exists('page', $_GET) ? $_GET['page'] : 'default';
        $this->initDB( $dbSettings['connectionString'], $dbSettings['dbUser'], $dbSettings['dbPwd'] );
        $this->setPage($page);
    }

    private function setPage( string $page ) {
        if( !empty($page) ) {
            $this->page = $page;
            $query = 'SELECT * FROM `system_pages` WHERE `page` LIKE ?';
            $this->logger->logEvent('query: '.$query . ' | page = '.$this->page, __FILE__, __LINE__, __FUNCTION__);
            $sth = $this->dbh->prepare( $query );
            // выполнение запроса
            $sth->execute( [$this->page] );
            $res = $sth->fetchAll();
            if( !empty($res) && count($res)==1 ) {
                $this->handler = $res[0];
            }
        }
        if( empty( $this->handler ) ) {
            $this->page = 'default';
            $this->handler = [
                'page' => 'default',
                'description' => 'Действие по-умолчанию',
                'controller' => 'OrderClass',
                'handler' => 'get_list'
            ];
        }
        $this->logger->logEvent('handler: '.var_export($this->handler, true), __FILE__, __LINE__, __FUNCTION__);
    }

    private function createController()
    {

        $controller = 'MyApp\Controller\\'.$this->handler['controller'];
        $this->logger->logEvent('going to create '.$controller, __FILE__, __LINE__, __FUNCTION__);
        $params = [
            $this->dbh,
            $this->logger
        ];
        return new $controller(...$params);
    }

    private function getHandlerFunction()
    {
        return $this->handler['handler'];
    }

    private function initDB( string $connectionString, string $dbUser, string $dbPwd )
    {
        $this->dbh = new PDO( $connectionString, $dbUser, $dbPwd );
        $this->dbh->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->logger->logEvent('Connected to DB!', __FILE__, __LINE__, __FUNCTION__);
    }

    /**
     * call handler to process request
     */
    public function run()
    {

        try {
            $this->logger->logEvent('curpage: ' . $this->page, __FILE__, __LINE__, __FUNCTION__);
            $controller = $this->createController();
            $handler = $this->getHandlerFunction();
            if ($this->handler['page'] == 'default') {
                $controller->$handler($this->twig);
            } else {
                $auth = new Controller\AuthClass($this->dbh, $this->logger);
                $perm = $auth->get_permission([$this->handler['id']]);
                if ($perm[0]['roleid'] == '2' or $perm[0]['roleid'] == '3') {
                    $controller->$handler($this->twig);
                } else {
                    $data = $auth->get_data([$_SESSION['login']]);
                    if ($data[0]['roleid'] == '1') {
                        $controller->$handler($this->twig);
                    } else echo "Ошибка доступа";
                }
            }
        }
        catch (Exception $e) {
            $this->logger->logEvent($e->getMessage(), $e->getFile(), $e->getLine(), $e->getTraceAsString());
            echo json_encode([]);
        }

    }
}
