<?php

class Database {
    private static $instance = null;
    private $conn;

    private function __construct() {
        $db_url = getenv('DATABASE_URL') ?: 'postgresql://neondb_owner:npg_f1ndjyuvbC6R@ep-plain-sky-aq9k1xu9-pooler.c-8.us-east-1.aws.neon.tech/neondb?sslmode=require';

        try {
            $url = parse_url($db_url);
            $host = $url['host'] ?? 'localhost';
            $port = $url['port'] ?? '5432';
            $user = $url['user'] ?? 'neondb_owner';
            $pass = $url['pass'] ?? 'npg_f1ndjyuvbC6R';
            $dbname = ltrim($url['path'] ?? '/neondb', '/');
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $dsn = "pgsql:host={$host};port={$port};dbname={$dbname}";
            if (strpos($db_url, 'sslmode=require') !== false) {
                $dsn .= ';sslmode=require';
            }
            $this->conn = new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            error_log('Database connection error: ' . $e->getMessage());
            die('Database connection failed. Please try again later.');
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->conn;
    }
}
