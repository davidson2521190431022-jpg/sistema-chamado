<?php

$servidor = "localhost";
$banco = "sistema_chamados";
$usuario = "root";
$senha = "";

try {

    $pdo = new PDO(
        "mysql:host=$servidor;dbname=$banco;charset=utf8",
        $usuario,
        $senha
    );

    $pdo->setAttribute(
        PDO::ATTR_ERRMODE,
        PDO::ERRMODE_EXCEPTION
    );

    echo "Banco conectado com sucesso!";

} catch (PDOException $e) {

    echo "Erro na conexão: " . $e->getMessage();

}
?>