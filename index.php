<?php

use App\DbCorrector;

require_once __DIR__ . '/vendor/autoload.php';


// $dbFirst это БД из которой мы накатываем изменения
// $dbProduct ЭТО БД в которую накатываем изменения.
// для подключения измените строки new PDO("mysql:host=ИМЯ_ХОСТА;dbname=ИМЯ_БАЗЫ_ДАННЫХ", "ИМЯ_ПОЛЬЗОВАТЕЛЯ", "ПАРОЛЬ")
try {
    $dbFirst = new PDO("mysql:host=localhost;dbname=dbFirst", "root", "");
    $dbProduct = new PDO("mysql:host=localhost;dbname=dbProduct", "root", "");
}catch (PDOException $exception){
    echo "Error DB " . $exception->getMessage();
    die();
}


$con = new DbCorrector($dbFirst, $dbProduct);

$con->correctDatabase();

$dbFirst = null;
$dbProduct = null;


