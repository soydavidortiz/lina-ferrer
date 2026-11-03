<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Solo ejecutable por consola.');
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

function preguntar(string $texto): string
{
    echo $texto;
    return trim((string) fgets(STDIN));
}

echo "\n=== LIRA FERRER · Crear administrador ===\n\n";

$nombre = preguntar('Nombre completo: ');
$email  = strtolower(preguntar('Email: '));
$pass   = preguntar('Contraseña (mín. 10 caracteres, con letras y números): ');

if ($nombre === '')                                   exit("❌ El nombre es obligatorio\n");
if (!filter_var($email, FILTER_VALIDATE_EMAIL))       exit("❌ Email inválido\n");
if (strlen($pass) < 10
    || !preg_match('/[A-Za-z]/', $pass)
    || !preg_match('/\d/', $pass))                    exit("❌ Contraseña débil\n");

$algoritmo = defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_DEFAULT;
$hash = password_hash($pass, $algoritmo);

try {
    $stmt = db()->prepare(
        "INSERT INTO admin_usuarios (nombre, email, password_hash, rol) VALUES (?, ?, ?, 'admin')"
    );
    $stmt->execute([$nombre, $email, $hash]);
    echo "\n✅ Administrador creado (ID " . db()->lastInsertId() . ")\n";
} catch (PDOException $e) {
    echo "\n❌ " . ($e->getCode() === '23000' ? 'Ese email ya está registrado' : $e->getMessage()) . "\n";
}
