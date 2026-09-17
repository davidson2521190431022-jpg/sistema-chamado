<?php

$url = getenv('DATABASE_URL');

if ($url) {
    $partes = parse_url($url);

    $servidor = $partes['host'] ?? 'localhost';
    $porta = $partes['port'] ?? 3306;
    $usuario = rawurldecode($partes['user'] ?? '');
    $senha = rawurldecode($partes['pass'] ?? '');
    $banco = isset($partes['path'])
        ? ltrim($partes['path'], '/')
        : '';
} else {
    $servidor = 'localhost';
    $porta = 3306;
    $usuario = 'root';
    $senha = '';
    $banco = 'sistema_chamados';
}

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