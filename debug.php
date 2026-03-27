<?php
// debug.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>PHP Shell Omgeving Debugger 🔬</h1><pre style='background: #f4f4f4; border: 1px solid #ccc; padding: 15px; border-radius: 5px; white-space: pre-wrap; word-wrap: break-word;'>";

$convert_path = '/usr/bin/convert';

// --- TEST 1: KAN PHP HET BESTAND ZIEN? ---
echo "<h2>Test 1: Controle op '$convert_path' vanuit PHP</h2>";
if (file_exists($convert_path)) {
    echo "✅ SUCCESS: `file_exists()` zegt dat het bestand bestaat.\n";
    if (is_executable($convert_path)) {
        echo "✅ SUCCESS: `is_executable()` zegt dat PHP het bestand mag uitvoeren.\n";
    } else {
        echo "❌ FOUT: `is_executable()` zegt dat PHP het bestand NIET mag uitvoeren. (Rechtenprobleem op het bestand zelf)\n";
    }
} else {
    echo "❌ FOUT: `file_exists()` zegt dat het bestand NIET bestaat vanuit het perspectief van PHP. (Waarschijnlijk een chroot jail)\n";
}
echo "<hr>";

// --- TEST 2: PROBEER EEN SIMPEL COMMANDO UIT TE VOEREN ---
echo "<h2>Test 2: Poging tot uitvoeren van `convert --version`</h2>";
$version_output = shell_exec($convert_path . ' --version 2>&1');
if ($version_output) {
    echo "✅ SUCCESS: Commando kon worden uitgevoerd. Output:\n";
    echo "----------------------------------------\n";
    echo htmlspecialchars(trim($version_output));
    echo "\n----------------------------------------\n";
} else {
    echo "❌ FOUT: Het commando kon niet worden uitgevoerd of gaf geen output. Dit bevestigt het probleem.\n";
}
echo "<hr>";

// --- TEST 3: WAT IS DE OMGEVING VAN DE WEBSERVER? ---
echo "<h2>Test 3: De volledige 'env' (omgeving) van de webserver</h2>";
$env_output = shell_exec('env 2>&1');
echo htmlspecialchars($env_output);

echo "</pre>";
?>
