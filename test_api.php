<?php
// test_api.php

// 1. Configuratie
$api_key = 'AIzaSyCGYCSmf06jN1Nl8eKDexLVdeJhe4FrOxw';
$image_path = 'test_foto.jpg'; // Zorg dat dit bestand bestaat
$model = 'gemini-flash-latest'; // Of 'gemini-3.1-flash'

if (!file_exists($image_path)) {
    die("Fout: Kan testafbeelding '$image_path' niet vinden.\n");
}

echo "Start API test voor bestand: $image_path\n";
echo "Geselecteerd model: $model\n\n";

$base64 = base64_encode(file_get_contents($image_path));
$mime = mime_content_type($image_path);

$url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$api_key}";

$data = [
    "contents" => [[
        "parts" => [
            ["text" => "Beschrijf zeer kort wat centraal staat op deze foto in maximaal 60 tekens. Gebruik uitsluitend letters, cijfers en spaties. Geen leestekens. Taal is Nederlands."],
            ["inline_data" => ["mime_type" => $mime, "data" => $base64]]
        ]
    ]]
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

echo "Verzoek verzenden naar Google servers...\n";
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

echo "HTTP Status Code: $http_code\n";

if ($curl_error) {
    die("cURL Fout: $curl_error\n");
}

echo "\n--- RAW JSON RESPONSE ---\n";
// Format de JSON netjes voor leesbaarheid
echo json_encode(json_decode($response), JSON_PRETTY_PRINT) ?: $response;
echo "\n-------------------------\n";

// Test de extractie-logica
$json = json_decode($response, true);
if (isset($json['error'])) {
    echo "\nAPI retourneerde een foutmelding. Controleer de details hierboven.\n";
} elseif (isset($json['candidates'][0]['content']['parts'][0]['text'])) {
    $text = trim($json['candidates'][0]['content']['parts'][0]['text']);
    $clean = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $text));
    $final_name = substr($clean, 0, 60) . ".webp";
    
    echo "\n[SUCCES] Geëxtraheerde tekst: '$text'\n";
    echo "[SUCCES] Gegenereerde bestandsnaam: $final_name\n";
} else {
    echo "\nOnverwachte response-structuur. Kan geen tekst extraheren.\n";
}
?>