<?php

header("Content-Type: application/json; charset=utf-8");

require_once "../config/conexao.php";

function enviarMensagemWhatsApp($destinatario, $mensagem)
{
    $token = getenv("WHATSAPP_ACCESS_TOKEN");
    $phoneNumberId = getenv("WHATSAPP_PHONE_NUMBER_ID");
    $versao = getenv("WHATSAPP_GRAPH_VERSION") ?: "v26.0";

    if (!$token || !$phoneNumberId) {
        return false;
    }

    $url = "https://graph.facebook.com/{$versao}/{$phoneNumberId}/messages";

    $dados = [
        "messaging_product" => "whatsapp",
        "to" => $destinatario,
        "type" => "text",
        "text" => [
            "body" => $mensagem
        ]
    ];

    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer " . $token,
            "Content-Type: application/json"
        ],
        CURLOPT_POSTFIELDS => json_encode($dados),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15
    ]);

    $resposta = curl_exec($ch);

    curl_close($ch);

    return $resposta;
}

/*
|--------------------------------------------------------------------------
| VERIFICAÇÃO DO WEBHOOK DA META
|--------------------------------------------------------------------------
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

/*
|--------------------------------------------------------------------------
| ACEITA SOMENTE POST DEPOIS DA VERIFICAÇÃO
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    http_response_code(405);

    echo json_encode([
        "erro" => "Metodo nao permitido"
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| LÊ O JSON
|--------------------------------------------------------------------------
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
|--------------------------------------------------------------------------
| IDENTIFICA SE É WHATSAPP OU TESTE NORMAL
|--------------------------------------------------------------------------
*/

$ehWhatsApp = (($dados["object"] ?? "") === "whatsapp_business_account");

if ($ehWhatsApp) {

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

    $cpf = "Nao informado";
    $matricula = "";
    $setor = "WhatsApp";
    $categoria = "Atendimento WhatsApp";
    $prioridade = "Media";

} else {

    /*
    |--------------------------------------------------------------------------
    | TESTE ANTIGO DA API
    |--------------------------------------------------------------------------
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
|--------------------------------------------------------------------------
| VALIDAÇÃO
|--------------------------------------------------------------------------
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
|--------------------------------------------------------------------------
| SALVA O CHAMADO
|--------------------------------------------------------------------------
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

    /*
    |--------------------------------------------------------------------------
    | RESPONDE AUTOMATICAMENTE NO WHATSAPP
    |--------------------------------------------------------------------------
    */

    if ($ehWhatsApp && $telefone !== "") {

        $respostaWhatsApp =
            "Olá, " . $nome . "! 👋\n\n" .
            "Seu chamado foi registrado com sucesso.\n\n" .
            "📋 Protocolo: " . $protocolo . "\n" .
            "📌 Status: Aberto\n" .
            "🏢 Setor: " . $setor . "\n\n" .
            "Em breve seu atendimento será analisado.";

        enviarMensagemWhatsApp(
            $telefone,
            $respostaWhatsApp
        );
    }

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