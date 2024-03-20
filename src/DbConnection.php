<?php

namespace App;
use PDO;
use PDOException;

class DbConnection
{
    // создаём в контструкторе все параметры подключения к БД
    private string $host;
    private string $dbname;
    private string $username;
    private string $password;
    private string $charset = 'utf8';
    private array $options = [
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];

    /**
     * @param string $host
     * @param string $dbname
     * @param string $username
     * @param string $password
     */
    public function __construct(string $host, string $dbname, string $username, string $password)
    {
        $this->host = $host;
        $this->dbname = $dbname;
        $this->username = $username;
        $this->password = $password;
    }

    // создаём подключение к БД через метод getConnection()
    public function getConnection()
    {
        $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset={$this->charset}";
        try {
            return new PDO($dsn, $this->username, $this->password, $this->options);
        }catch (PDOException $e){
            echo "DB error: " . $e->getMessage();
            die();
        }
    }
}