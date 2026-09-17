<?php

$servidor = getenv('DB_HOST') ?: 'localhost';
$porta = getenv('DB_PORT') ?: '3306';
$banco = getenv('DB_NAME') ?: 'sistema_chamados';
$usuario = getenv('DB_USER') ?: 'root';
$senha = getenv('DB_PASSWORD') ?: '';

try {
    $pdo = new PDO(
        "mysql:host=$servidor;port=$porta;dbname=$banco;charset=utf8mb4",
        $usuario,
        $senha
    );

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    die("Erro na conexão com o banco: " . $e->getMessage());
}