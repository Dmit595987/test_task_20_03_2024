<?php

use App\DbCorrector;

require_once __DIR__ . '/vendor/autoload.php';

$dbFirst = new PDO("mysql:host=localhost;dbname=dbFirst", "root", "");
$dbProduct = new PDO("mysql:host=localhost;dbname=dbProduct", "root", "");

$con = new DbCorrector($dbFirst, $dbProduct);

$con->correctDatabase();

$dbFirst = null;
$dbProduct = null;


