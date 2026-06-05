<?php
/**
 * PostgreSQL Schema Migration
 * 
 * Reads schema.sql and executes each statement against the Neon database.
 * Usage: php database/migrations/001_initial_schema.php
 */

$db_url = getenv('DATABASE_URL') ?: 'postgresql://neondb_owner:npg_f1ndjyuvbC6R@ep-plain-sky-aq9k1xu9-pooler.c-8.us-east-1.aws.neon.tech/neondb?sslmode=require';

try {
    $url = parse_url($db_url);
    $host = $url['host'] ?? 'localhost';
    $port = $url['port'] ?? '5432';
    $user = $url['user'] ?? 'neondb_owner';
    $pass = $url['pass'] ?? 'npg_f1ndjyuvbC6R';
    $dbname = ltrim($url['path'] ?? '/neondb', '/');

    $dsn = "pgsql:host={$host};port={$port};dbname={$dbname}";
    if (strpos($db_url, 'sslmode=require') !== false) {
        $dsn .= ';sslmode=require';
    }

    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    echo "Connected to database successfully.\n";

    $schema_file = __DIR__ . '/../schema.sql';

    if (!file_exists($schema_file)) {
        die("Schema file not found: {$schema_file}\n");
    }

    $sql = file_get_contents($schema_file);

    // Remove comment lines and blank lines
    $lines = explode("\n", $sql);
    $clean_lines = [];
    foreach ($lines as $line) {
        $trimmed = trim($line);
        if (empty($trimmed) || strpos($trimmed, '--') === 0) {
            // Keep blank lines and comment lines for structure detection
            if (empty($trimmed)) {
                $clean_lines[] = '';
            }
            // Skip comment lines entirely
            continue;
        }
        $clean_lines[] = $line;
    }

    $clean_sql = implode("\n", $clean_lines);

    // Split by semicolons, respecting that semicolons inside strings should not split
    $statements = [];
    $current = '';
    $in_string = false;
    $string_char = '';
    $len = strlen($clean_sql);

    for ($i = 0; $i < $len; $i++) {
        $char = $clean_sql[$i];

        if ($in_string) {
            $current .= $char;
            if ($char === $string_char && ($i === 0 || $clean_sql[$i - 1] !== '\\')) {
                $in_string = false;
            }
        } else {
            if ($char === "'" || $char === '"') {
                $in_string = true;
                $string_char = $char;
                $current .= $char;
            } elseif ($char === ';') {
                $trimmed = trim($current);
                if (!empty($trimmed)) {
                    $statements[] = $trimmed;
                }
                $current = '';
            } else {
                $current .= $char;
            }
        }
    }

    // Handle trailing statement without semicolon
    $trimmed = trim($current);
    if (!empty($trimmed)) {
        $statements[] = $trimmed;
    }

    echo "Found " . count($statements) . " SQL statements to execute.\n";

    $pdo->beginTransaction();

    try {
        $count = 0;
        foreach ($statements as $stmt) {
            if (empty(trim($stmt))) continue;
            echo "Executing: " . substr($stmt, 0, 80) . (strlen($stmt) > 80 ? '...' : '') . "\n";
            $pdo->exec($stmt);
            $count++;
        }

        $pdo->commit();
        echo "\n✓ Migration completed successfully. {$count} statements executed.\n";

    } catch (Exception $e) {
        $pdo->rollBack();
        echo "\n✗ Migration failed: " . $e->getMessage() . "\n";
        echo "Transaction rolled back.\n";
        exit(1);
    }

} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage() . "\n";
    exit(1);
}
