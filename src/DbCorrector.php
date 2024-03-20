<?php

namespace App;

use PDO;

class DbCorrector
{
    // $dbFirst это база из которой накатываем изменения
    //$dbProduct это база в которую накатываем изменения
    private PDO $dbFirst;
    private PDO $dbProduct;

    // в конструкторе создаём уже сделанные подключения в классе DbConnection который обеспечивает коннект с MySQL
    public function __construct(PDO $dbFirst, PDO $dbProduct) {
        $this->dbFirst = $dbFirst;
        $this->dbProduct = $dbProduct;
    }

    public function correctDatabase() {
        $tables = $this->getTables();

        foreach($tables as $table) {
            $columnsFirst = $this->getColumns($table, $this->dbFirst);
            $columnsProduct = $this->getColumns($table, $this->dbProduct);

            if(count(array_diff_assoc($columnsFirst, $columnsProduct)) > 0) {
                $this->updateColumns($table, $columnsFirst, $columnsProduct);
            }

            $fieldsFirst = $this->getFields($table, $this->dbFirst);
            $fieldsProduct = $this->getFields($table, $this->dbProduct);

            if(count(array_diff_assoc($fieldsFirst, $fieldsProduct)) > 0){
                $this->updateFields($table, $fieldsFirst, $fieldsProduct);
            }
        }
    }



    // апдейтим
    private function updateColumns($table, $columnsFirst, $columnsProduct) {
        $columnsToAdd = array_diff($columnsFirst, $columnsProduct);

        foreach($columnsToAdd as $column) {

            $stmtType = $this->dbFirst->prepare("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = :tableName  AND column_name = :columnName");

            $stmtType->bindParam(":tableName", $table);
            $stmtType->bindParam(":columnName", $column);
            $stmtType->execute();
            $columnInfo = $stmtType->fetch(PDO::FETCH_ASSOC);

            $columnInfo = $columnInfo["COLUMN_TYPE"];

            $stmt = $this->dbProduct->prepare("ALTER TABLE $table ADD $column $columnInfo");
            $stmt->execute();
        }
    }

    // читаем все таблицы в БД $dbFirst
    private function getTables() {
        $stmt = $this->dbFirst->query('SHOW TABLES');
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    private function getColumns($table, $pdo) {
        $stmt = $pdo->query("SHOW COLUMNS FROM $table");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    private function getFields($table, $pdo){
        $stmt = $pdo->query("SELECT * FROM $table");
        $fields = $stmt->fetchall(PDO::FETCH_ASSOC);
        foreach ($fields as $field){
            foreach ($field as $key => $value){
                if(!$value){
                    $field[$key] = 'NULL';
                }
            }
        }
        var_dump($fields);
        return $fields;
    }

    private function updateFields($table, $fieldsFirst, $fieldsProduct){
        $fieldsToAdd = array_diff($fieldsFirst, $fieldsProduct);

        foreach ($fieldsToAdd as $field){
            $prepare_keys = implode(', ', array_slice(array_keys($field), 1));
            $prepare_values = implode(', ', array_slice(array_values($field), 1));
            $stmt = $this->dbProduct->prepare("INSERT INTO $table($prepare_keys) VALUES ($prepare_values)");

        }
    }
}