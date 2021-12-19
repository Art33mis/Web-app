<?php

const DEBUG = false;
const MODULES_DIR = '../safeplace/';

$dbSettings = [
    'connectionString' => 'mysql:host=localhost;dbname=Sample;charset=utf8',
    'dbUser' => 'artyom_avtaykin',
    'dbPwd' => 'povelitel'
];
$arr_cookie_options = array (
    'expires' => time() + 3600,
    'path' => '/',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'strict'
);

