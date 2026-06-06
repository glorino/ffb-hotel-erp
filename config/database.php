<?php

class Database {
    private static $instance = null;
    private $conn;

    private function __construct() {
        $db_url = getenv('DATABASE_URL');

        try {
            $url = parse_url($db_url);
            $host = $url['host'] ?? 'localhost';
            $port = $url['port'] ?? '5432';
            $user = $url['user'] ?? '';
            $pass = $url['pass'] ?? '';
            $dbname = ltrim($url['path'] ?? '', '/');
            $endpoint_id = explode('.', $host)[0] ?? '';
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $dsn = "pgsql:host={$host};port={$port};dbname={$dbname};sslmode=require";
            if ($endpoint_id && $endpoint_id !== $host) {
                $dsn .= ";options=endpoint={$endpoint_id}";
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
