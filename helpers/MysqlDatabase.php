<?php
require_once "../config.php";

class MysqlDatabase{
    private PDO $connection;
    private array $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_CASE => PDO::CASE_NATURAL,
        PDO::ATTR_ORACLE_NULLS => PDO::NULL_NATURAL,
    ];

    /**
     * MysqlDatabase constructor.
     */
    public function __construct(){
        try {
            $this->setConnection(new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS, $this->getOptions()));
        } catch(PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
    }

    /**
     * @return PDO
     */
    public function getConnection(): PDO
    {
        return $this->connection;
    }

    /**
     * @param PDO $connection
     */
    public function setConnection(PDO $connection): void
    {
        $this->connection = $connection;
    }

    /**
     * @return array
     */
    public function getOptions(): array{
        return $this->options;
    }
    /**
     * @param array $options
     */
    public function setOptions(array $options): void{
        $this->options = $options;
    }

    public function prepareStatement($query): PDOStatement{
        return $this->getConnection()->prepare($query);
    }

}
