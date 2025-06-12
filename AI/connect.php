<?php
header('Content-Type: application/json');

// Leggi l'input JSON
$data = json_decode(file_get_contents('php://input'), true);
$message = $data['message'] ?? '';

// Chiave API Gemini (sostituisci con la tua)
$apiKey = 'AIzaSyA9Lkkkf5PMnFU96d50FS8_S_MK24bksMY';

// Endpoint Google PaLM / Gemini
$url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=$apiKey";
// Corpo della richiesta per Gemini
$postData = [
    'contents' => [
        [
            'parts' => [
                ['text' => $message]
            ]
        ]
    ]
];

// Inizializza cURL
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);

// Esegui la richiesta
$response = curl_exec($ch);
curl_close($ch);

// Elabora la risposta
$result = json_decode($response, true);

if (!isset($result['candidates'][0]['content']['parts'][0]['text'])) {
    echo json_encode([
        'reply' => 'Errore API Gemini: ' . json_encode($result)
    ]);
    exit;
}

$reply = $result['candidates'][0]['content']['parts'][0]['text'];
echo json_encode(['reply' => $reply]);