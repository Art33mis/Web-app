<?php

require_once '../safeplace/settings.inc.php';
require_once  MODULES_DIR.'autoloader.php';
use MyApp\EventHandler2;
use MyApp\Logger\FileLoggerBuf;

try {
    //echo hash("sha512", "petrhash");
    session_start();
    setcookie('test', 'test', $arr_cookie_options);
    $logger = new FileLoggerBuf('tmp.log');
    $app = new EventHandler2($dbSettings, $logger);
    $app->run();
//session_destroy();
}
catch (Exception $e) {
    $this->logger->logEvent($e->getMessage(), $e->getFile(), $e->getLine(), $e->getTraceAsString());
    echo json_encode([]);
}




