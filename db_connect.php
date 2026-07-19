<?php
ob_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/php_errors.log');

function loadEnvFile(string $path): void
{
    if (!is_file($path) || !is_readable($path)) {
        return;
    }

    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = array_map('trim', explode('=', $line, 2));
        $value = trim($value, "\"'");

        if (getenv($key) === false) {
            putenv("$key=$value");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}

loadEnvFile(__DIR__ . '/.env');

$databaseUrl = getenv('DATABASE_URL') ?: '';
$supabaseDbPassword = getenv('SUPABASE_DB_PASSWORD') ?: '';
if (str_contains($databaseUrl, '<password>')) {
    $databaseUrl = '';
}

if ($databaseUrl === '' && $supabaseDbPassword !== '' && $supabaseDbPassword !== 'paste-your-supabase-db-password-here') {
    $databaseUrl = sprintf(
        'postgresql://postgres:%s@db.rhbpmopeylzyahwtmoyt.supabase.co:5432/postgres?sslmode=require',
        rawurlencode($supabaseDbPassword)
    );
}

$dbDriver = 'mysql';

try {
    if ($databaseUrl !== '') {
        $parts = parse_url($databaseUrl);
        if ($parts === false || empty($parts['scheme'])) {
            throw new PDOException('Invalid DATABASE_URL');
        }

        $dbDriver = str_starts_with($parts['scheme'], 'postgres') ? 'pgsql' : 'mysql';
        $host = $parts['host'] ?? 'localhost';
        $port = $parts['port'] ?? ($dbDriver === 'pgsql' ? 5432 : 3306);
        $dbname = isset($parts['path']) ? ltrim($parts['path'], '/') : '';
        $username = isset($parts['user']) ? urldecode($parts['user']) : '';
        $password = isset($parts['pass']) ? urldecode($parts['pass']) : '';
        $query = [];
        if (!empty($parts['query'])) {
            parse_str($parts['query'], $query);
        }

        if ($dbDriver === 'pgsql') {
            $sslmode = $query['sslmode'] ?? 'require';
            $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=$sslmode";
        } else {
            $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
        }
    } else {
        if (getenv('VERCEL')) {
            throw new PDOException('DATABASE_URL is not configured. Add the Supabase Postgres connection string in Vercel project environment variables.');
        }

        $host = 'localhost';
        $dbname = 'debuglia';
        $username = 'root';
        $password = '';
        $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    }

    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (PDOException $e) {
    // Log error instead of displaying it
    error_log('Database connection failed: ' . $e->getMessage());
    
    ob_end_clean();
    // JSON response and included in API scripts 
    if (defined('JSON_RESPONSE')) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit;
    }

    http_response_code(500);
    exit;
}

ob_end_clean();
?>
