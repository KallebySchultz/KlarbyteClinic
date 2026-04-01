<?php
// EnterClinic - Configuração do Banco de Dados
// Ajuste os dados abaixo conforme seu ambiente XAMPP

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'enterclinic');

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            die('<div style="font-family:sans-serif;padding:2rem;color:#c0392b;">
                <h2>Erro de conexão com o banco de dados</h2>
                <p>' . htmlspecialchars($e->getMessage()) . '</p>
                <p>Verifique se o XAMPP está rodando e se executou o arquivo <strong>database.sql</strong>.</p>
            </div>');
        }
    }
    return $pdo;
}

function sanitize(string $s): string {
    return htmlspecialchars(trim($s), ENT_QUOTES, 'UTF-8');
}

function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

function flash(string $msg, string $type = 'success'): void {
    $_SESSION['flash'] = ['msg' => $msg, 'type' => $type];
}

function getFlash(): ?array {
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

if (session_status() === PHP_SESSION_NONE) session_start();

date_default_timezone_set('America/Sao_Paulo');
