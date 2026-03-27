<?php
// test_write.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$dir = 'webp_images';
$file = $dir . '/test_bestand.txt';
$content = 'Als je dit leest, kan PHP schrijven! Tijd: ' . date('Y-m-d H:i:s');

echo "<h1>PHP Schrijftest 🧪</h1>";
echo "<p>We proberen een bestand aan te maken in de map: <strong>" . realpath($dir) . "</strong></p><hr>";

// Controleer of de map bestaat
if (!is_dir($dir)) {
    die("<h2>❌ MISLUKT</h2><p>Fout: De map '$dir' bestaat niet. Controleer de naam.</p>");
}

// De belangrijkste check: Wat denkt PHP zelf van de rechten?
if (!is_writable($dir)) {
    die("<h2>❌ MISLUKT</h2><p>Fout: PHP rapporteert dat de map <strong>'$dir' NIET schrijfbaar is</strong>. Dit wijst op een probleem met de rechten in een van de bovenliggende mappen (bijv. /var/www/ of /var/www/verhalenherstel.site/).</p>");
}

echo "<p>✅ Goed nieuws: PHP's `is_writable()` functie zegt dat de map '$dir' schrijfbaar is.</p>";
echo "<p>We proberen nu daadwerkelijk het bestand '$file' aan te maken...</p>";

// Poging tot schrijven
if (file_put_contents($file, $content) !== false) {
    echo "<h2>✅ SUCCES!</h2>";
    echo "<p>Bestand '$file' is succesvol aangemaakt.</p>";
    echo "<p>Het probleem ligt waarschijnlijk bij de configuratie van `shell_exec` of ImageMagick zelf.</p>";
    echo "<pre>Rechten van het nieuwe bestand:\n" . htmlspecialchars(shell_exec('ls -l ' . escapeshellarg($file))) . "</pre>";
} else {
    echo "<h2>❌ MISLUKT!</h2>";
    echo "<p><strong>Dit is de kern van het probleem.</strong> Hoewel `is_writable()` zegt dat het zou moeten werken, mislukt `file_put_contents()` toch.</p>";
    echo "<p>Dit wordt bijna altijd veroorzaakt door een hoger-niveau beveiligingssysteem zoals <strong>AppArmor</strong> of <strong>SELinux</strong>, dat de webserver verbiedt om bestanden te schrijven, zelfs als de standaard Linux-rechten correct zijn.</p>";
}
?>
