<?php

use FpDbTest\Database;
use FpDbTest\DatabaseTest;

spl_autoload_register(function ($class) {
    $a = array_slice(explode('\\', $class), 1);
    if (!$a) {
        throw new Exception();
    }
    $filename = implode('/', [__DIR__, ...$a]) . '.php';
    require_once $filename;
});

if (!function_exists('array_is_list')) {
    function array_is_list(array $array)
    {
        return $array === array_values($array);
    }
}

$mysqli = @new mysqli(getenv('DB_HOST')?:'localhost',
    getenv('DB_USER')?:'root', 
    getenv('DB_PASS')?:'password',
    getenv('DB_NAME')?:'database',
    getenv('DB_PORT')?:3306);

if ($mysqli->connect_errno) {
    throw new Exception($mysqli->connect_error);
}

$db = new Database($mysqli);
$test = new DatabaseTest($db);
$test->testBuildQuery();

exit('OK');
