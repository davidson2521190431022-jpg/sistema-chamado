<?php

$url = "https://vertical-footless-quintet.ngrok-free.dev/sistema-chamado/API/webhook.php";

$dados = [
    "nome" => "Davidson",
    "cpf" => "000.000.000-00",
    "matricula" => "12345",
    "setor" => "Administracao",
    "categoria" => "Computador",
    "descricao" => "Meu computador nao esta ligando",
    "prioridade" => "Alta"
];

$ch = curl_init($url);

curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($dados));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json"
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$resposta = curl_exec($ch);

if ($resposta === false) {
    echo "Erro: " . curl_error($ch);
} else {
    echo "<h2>Resposta do Webhook:</h2>";
    echo "<pre>";
    echo htmlspecialchars($resposta);
    echo "</pre>";
}

curl_close($ch);

?>