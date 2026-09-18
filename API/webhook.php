<?php

header("Content-Type: application/json; charset=utf-8");

require_once "../config/conexao.php";

/*
| VERIFICAÇÃO DO WEBHOOK DA META
*/

if ($_SERVER["REQUEST_METHOD"] === "GET") {

    $modo = $_GET["hub_mode"] ?? "";
    $token = $_GET["hub_verify_token"] ?? "";
    $desafio = $_GET["hub_challenge"] ?? "";

    $tokenCorreto = getenv("WHATSAPP_VERIFY_TOKEN") ?: "";

    if (
        $modo === "subscribe" &&
        $tokenCorreto !== "" &&
        hash_equals($tokenCorreto, $token)
    ) {
        http_response_code(200);
        echo $desafio;
        exit;
    }

    http_response_code(403);
    echo "Token de verificacao invalido";
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "GET") {

    $modo = $_GET["hub_mode"] ?? "";
    $token = $_GET["hub_verify_token"] ?? "";
    $desafio = $_GET["hub_challenge"] ?? "";

    $tokenCorreto = getenv("WHATSAPP_VERIFY_TOKEN") ?? "";

    if (
        $modo === "subscribe" &&
        $tokenCorreto !== "" &&
        hash_equals($tokenCorreto, $token)
    ) {
        http_response_code(200);
        echo $desafio;
        exit;
    }

    http_response_code(403);
    echo "Token de verificacao invalido";
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);

    echo json_encode([
        "erro" => "Metodo nao permitido"
    ]);

    exit;
}

/*
| LÊ O JSON
*/

$conteudo = file_get_contents("php://input");
$dados = json_decode($conteudo, true);

if (!$dados) {
    http_response_code(400);

    echo json_encode([
        "erro" => "JSON invalido"
    ]);

    exit;
}

/*
| WEBHOOK DA META
*/

if (($dados["object"] ?? "") === "whatsapp_business_account") {

    $value = $dados["entry"][0]["changes"][0]["value"] ?? [];

    if (!isset($value["messages"][0])) {
        http_response_code(200);

        echo json_encode([
            "status" => "Evento recebido"
        ]);

        exit;
    }

    $mensagem = $value["messages"][0];

    $nome = $value["contacts"][0]["profile"]["name"] ?? "Cliente";
    $telefone = $mensagem["from"] ?? "";
    $tipo = $mensagem["type"] ?? "";

    if ($tipo === "text") {
        $descricao = $mensagem["text"]["body"] ?? "";
    } else {
        $descricao = "Mensagem recebida pelo WhatsApp (" . $tipo . ")";
    }

    /*
     * Nesta primeira versão:
     * - CPF ainda não foi informado
     * - matrícula ainda não foi informada
     * - setor recebe "WhatsApp"
     * - categoria recebe "Atendimento WhatsApp"
     *
     * Depois podemos criar uma conversa automática
     * para perguntar CPF, setor, categoria etc.
     */

    $cpf = "Nao informado";
    $matricula = "";
    $setor = "WhatsApp";
    $categoria = "Atendimento WhatsApp";
    $prioridade = "Media";
} else {

    /*
     | MANTÉM O TESTE ANTIGO DA API
     */

    $nome = $dados["nome"] ?? "";
    $cpf = $dados["cpf"] ?? "";
    $matricula = $dados["matricula"] ?? "";
    $setor = $dados["setor"] ?? "";
    $categoria = $dados["categoria"] ?? "";
    $descricao = $dados["descricao"] ?? "";
    $prioridade = $dados["prioridade"] ?? "Media";
}

/*
| VALIDAÇÃO
*/

if (
    $nome === "" ||
    $cpf === "" ||
    $setor === "" ||
    $categoria === "" ||
    $descricao === ""
) {
    http_response_code(400);

    echo json_encode([
        "erro" => "Dados obrigatorios ausentes"
    ]);

    exit;
}

/*
| SALVA O CHAMADO
*/

try {

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

    http_response_code(200);

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

    http_response_code(500);

    echo json_encode([
        "erro" => "Erro ao criar chamado",
        "detalhes" => $e->getMessage()
    ]);
}
