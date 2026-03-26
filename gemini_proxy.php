<?php
// Configuración inicial
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Leer la solicitud JSON
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['question']) || !isset($input['context'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Solicitud inválida']);
    exit;
}

// Configuración de la API de Gemini
$apiKey = 'AIzaSyA6N2s2wwaUVkQb1DsaXex64y44kCixsMY'; // Reemplaza con tu API key de Google AI
$endpoint = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=' . $apiKey;

// Crear el prompt para Gemini
$question = $input['question'];
$context = $input['context'];

$prompt = "Eres un asistente experto en el universo de One Piece y específicamente en las reglas del foro de rol 'One Piece Gaiden'. 
Usa el siguiente contexto para responder la pregunta del usuario de manera concisa pero detallada.
No inventes información que no esté en el contexto proporcionado.
Si la pregunta no está relacionada con One Piece o el foro, indica amablemente que sólo puedes responder preguntas sobre ese tema.

CONTEXTO:
$context

PREGUNTA DEL USUARIO:
$question

RESPUESTA:";

// Configurar la solicitud a la API de Gemini
$data = [
    'contents' => [
        [
            'parts' => [
                ['text' => $prompt]
            ]
        ]
    ],
    'generationConfig' => [
        'temperature' => 0.4,
        'maxOutputTokens' => 800,
        'topP' => 0.95,
        'topK' => 40
    ]
];

// Realizar la solicitud a la API
$ch = curl_init($endpoint);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Procesar la respuesta
if ($httpcode == 200) {
    $responseData = json_decode($response, true);
    
    if (isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
        $text = $responseData['candidates'][0]['content']['parts'][0]['text'];
        echo json_encode(['text' => $text]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Formato de respuesta inesperado', 'response' => $responseData]);
    }
} else {
    http_response_code($httpcode);
    echo json_encode(['error' => 'Error en la API de Gemini', 'response' => $response]);
}
?>