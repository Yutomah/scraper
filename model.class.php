<?php

require_once('config.php');
class Model
{
    private static $config;
    private static $connection_str_to_db;
    private static $connection_str;

    static public function initialize($config)
    {
        self::$config = $config;

        $db_name = $config['db_name'];
        $port = $config['port'];
        $host = $config['host'];

        self::$connection_str = "mysql:host=$host;port=$port";
        self::initialize_db();

        self::$connection_str_to_db = "mysql:dbname=$db_name;host=$host;port=$port";
        self::initialize_tables();
    }



    static private function connect($connection_str)
    {
        try {
            return new PDO($connection_str, self::$config['db_user'], self::$config['db_password']);
        } catch (PDOException $e) {
            return null;
        }
    }

    static private function initialize_db()
    {
        $conn = self::connect(self::$connection_str);

        if ($conn == null) {
            return;
        }

        $db_name = self::$config['db_name'];

        $sql_str = "CREATE DATABASE IF NOT EXISTS $db_name";
        $conn->exec($sql_str);

        $conn = null;
    }

    static private function initialize_tables()
    {
        $conn = self::connect(self::$connection_str_to_db);

        if ($conn == null) {
            return;
        }

        $dt_lots = self::$config['dt_lots'];
        $sql_str = "CREATE TABLE IF NOT EXISTS $dt_lots (
            lotNumber VARCHAR(50) PRIMARY KEY, 
            organizer VARCHAR(300), 
            lotLink VARCHAR(300), 
            beginDate DATE)";
        $conn->exec($sql_str);


        $dt_files = self::$config['dt_files'];
        $sql_str = "CREATE TABLE IF NOT EXISTS $dt_files ( 
            link VARCHAR(300) PRIMARY KEY, 
            name VARCHAR(300), 
            lotNumber VARCHAR(300),
            FOREIGN KEY (lotNumber) REFERENCES $dt_lots (lotNumber))";
        $conn->exec($sql_str);

        $conn = null;
    }

    static public function add_items($lots)
    {
        $conn = self::connect(self::$connection_str_to_db);

        if ($conn == null) {
            return false;
        }

        foreach ($lots as $lot) {
            $dt_lots = self::$config['dt_lots'];
            $sql_str = "INSERT INTO $dt_lots(lotNumber, organizer, lotLink, beginDate) 
            VALUES (:lotNumber, :organizer, :lotLink, :beginDate)
            ON DUPLICATE KEY UPDATE organizer = :organizer, lotLink = :lotLink, beginDate = :beginDate";

            $stmt = $conn->prepare($sql_str);

            $stmt->execute([
                ':lotNumber' => $lot['lotNumber'],
                ':organizer' => $lot['organizer'],
                ':lotLink' => $lot['lotLink'],
                ':beginDate' => self::transformToDateFormat($lot['beginDate']),
            ]);

            foreach ($lot['files'] as $file) {
                $dt_files = self::$config['dt_files'];
                $sql_str = "INSERT INTO $dt_files(link, name, lotNumber) 
                VALUES (:link, :name, :lotNumber)
                ON DUPLICATE KEY UPDATE name = :name, lotNumber = :lotNumber";

                $stmt = $conn->prepare($sql_str);

                $stmt->execute([
                    ':name' => $file['name'],
                    ':link' => $file['link'],
                    ':lotNumber' => $lot['lotNumber'],
                ]);
            }
        }
        return true;
    }

    private static function transformToDateFormat($date)
    {

        if (!preg_match("/^[0-9]{2}[.][0-9]{2}[.][0-9]{4}$/", $date)) {
            return null;
        }

        $date = explode(".", $date);
        return join(":", array_reverse($date));
    }
}
