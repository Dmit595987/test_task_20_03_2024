<?php

namespace App;

use PDO;
use PDOException;

class DbCorrector
{
    // $dbFirst это база из которой накатываем изменения
    private PDO $dbFirst;
    //$dbProduct это база в которую накатываем изменения
    private PDO $dbProduct;

    // в конструкторе создаём уже сделанные подключения в классе DbConnection который обеспечивает коннект с MySQL
    public function __construct(PDO $dbFirst, PDO $dbProduct) {
        $this->dbFirst = $dbFirst;
        $this->dbProduct = $dbProduct;
    }

    // начинаем корректировку,  эта функция публичная плюс конструктор
    public function correctDatabase() {
        $this->getAllTables($this->dbFirst, $this->dbProduct);
        $tables = $this->getTables($this->dbFirst);
        foreach($tables as $table) {
            $fieldsFirst = $this->getFields($table, $this->dbFirst);
            $fieldsProduct = $this->getFields($table, $this->dbProduct);
            if(count(array_diff_assoc($fieldsFirst, $fieldsProduct)) > 0){
                $this->updateFields($table, $fieldsFirst, $fieldsProduct);
            }
        }
    }


    // читаем все таблицы в БД
    private function getTables($pdo): array {
        $stmt = $pdo->query('SHOW TABLES');
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    // получаем все записи нужной нам таблицы
    private function getFields($table, $pdo): array{
        $stmt = $pdo->query("SELECT * FROM $table");
        return $stmt->fetchall(PDO::FETCH_ASSOC);

    }

    // апдейтим уже созданную новую таблицу. А именно переносим в неё все записи из таблицы в dbFirst
    private function updateFields($table, $fieldsFirst, $fieldsProduct){
        $fieldsToAdd = array_diff($fieldsFirst, $fieldsProduct);

        foreach ($fieldsToAdd as $field){
            $prepare_keys = implode(', ', array_slice(array_keys($field), 1));
            $prepare_values = [];;
            foreach (array_slice(array_values($field), 1) as $value){
                if(!$value){
                    array_push($prepare_values, "NULL");
                }else{
                    array_push($prepare_values, "\"" .$value . "\"" );
                }
            }
            $prepare_values = implode(', ', $prepare_values);

            $query = "INSERT INTO $table ($prepare_keys) VALUES ($prepare_values)";

            $stmt = $this->dbProduct->prepare($query);

            try{
                $stmt->execute();
            }catch (PDOException $ex){
                var_dump($ex->getMessage());

            }
        }
    }

    // получаем все таблицы из dbFirst и dbProduct Потом сравниваем их и отправляем уникальные на updateDBProduct
    private function getAllTables($dbFirst, $dbProduct){
        $tablesDBFirst = $this->getTables($dbFirst);
        $tablesDBProduct = $this->getTables($dbProduct);
        if(count(array_diff($tablesDBFirst, $tablesDBProduct)) > 0){
            $tables = array_diff($tablesDBFirst, $tablesDBProduct);
            foreach ($tables as $table){
                $stmt = $dbFirst->query("DESCRIBE $table");
                $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $this->updateDBProduct($table, $columns);
            }
        }
    }

    // создаем таблицы в DBProduct
    private function updateDBProduct($table, $columns){
        $values = [];
        foreach ($columns as $column){
            array_push($values, implode(" ",
                [$column['Field'],
                    $column['Type'],
                    $column['Key'] ? "PRIMARY KEY" : '',
                    $column['Extra'] ?? '',
                    $column['Default'] ? "DEFAULT" . ($column['Default']) : "",
                    ($column['Null'] === "NO") ? 'NOT NULL' : ($column['Null'] === 'YES' ? 'DEFAULT NULL' : '')
                    ]));
        }

        $values = implode(', ', $values);
        $query = "CREATE TABLE $table ($values)";
        $stmt = $this->dbProduct->prepare($query);
        $stmt->execute();
    }
}