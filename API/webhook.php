<?php

header("Content-Type: application/json; charset=utf-8");

require_once "../config/conexao.php";

// Só aceita POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode([
        "erro" => "Apenas POST é permitido"
    ]);
    exit;
}

// Recebe os dados enviados
$conteudo = file_get_contents("php://input");
$dados = json_decode($conteudo, true);

// Verifica se o JSON é válido
if (!$dados) {
    echo json_encode([
        "erro" => "JSON inválido"
    ]);
    exit;
}

// Recebe os campos
$nome = $dados["nome"] ?? "";
$cpf = $dados["cpf"] ?? "";
$matricula = $dados["matricula"] ?? "";
$setor = $dados["setor"] ?? "";
$categoria = $dados["categoria"] ?? "";
$descricao = $dados["descricao"] ?? "";
$prioridade = $dados["prioridade"] ?? "Media";

// Verifica campos obrigatórios
if (
    $nome == "" ||
    $cpf == "" ||
    $setor == "" ||
    $categoria == "" ||
    $descricao == ""
) {
    echo json_encode([
        "erro" => "Preencha nome, CPF, setor, categoria e descrição"
    ]);
    exit;
}

try {

    // Gera um protocolo
    $protocolo = date("YmdHis");

    $sql = "INSERT INTO chamados
            (nome, cpf, matricula, setor, categoria, descricao, prioridade, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'Aberto')";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $nome,
        $cpf,
        $matricula,
        $setor,
        $categoria,
        $descricao,
        $prioridade
    ]);

    echo json_encode([
        "status" => "Chamado criado com sucesso!",
        "protocolo" => $protocolo,
        "nome" => $nome,
        "cpf" => $cpf,
        "matricula" => $matricula,
        "setor" => $setor,
        "categoria" => $categoria,
        "descricao" => $descricao,
        "prioridade" => $prioridade,
        "status_chamado" => "Aberto"
    ]);

} catch (PDOException $e) {

    echo json_encode([
        "erro" => "Erro ao criar chamado",
        "detalhes" => $e->getMessage()
    ]);
}

?>