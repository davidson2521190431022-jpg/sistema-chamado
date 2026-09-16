<?php
 header("content-type: aplication/json");
    require_once "../config?conexao.php";
    $dados =
    json_decode(file_get_Contents("php:// input"), true);

    if 
    (!isset($dados["nome"])||
    !isset($dados["telefone"]) ||
    !isset($dados["descricao"]  )); {
    echo json_encode([
    "Erro" => "nome, telefone, e descrição são obrigatórios "
    ]);
    exit;
    }
    $sql = "INSERT INTO chamados
    (nome, telefone, descricao prioridade)
    VALUES (?, ?, ?, ?)";

    $stmt = $pdo ->prepare($sql);
    $stmt->execute ([
    $dados["nome"],
    $dados["telefone"],
    $dados["descricao"],
    $dados["prioridade"] ?? "normal"
    ]);
    echo json_encode([
    "sucesso" => true,
    "mensagem" => "chamado criado com sucesso",
    "id" =>  $pdo->lastInsertID()

    ]);