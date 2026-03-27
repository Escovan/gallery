<?php
// Laad de configuratie
require_once 'config.php';

// --- VERBORGEN TRIGGER: RESCAN ---
if (isset($_GET['rescan']) && $_GET['rescan'] === 'true') {
    $old_files = glob(IMAGE_DIR_PROCESSED . '/*.webp');
    foreach ($old_files as $file) {
        @unlink($file);
    }
    header("Location: index.php");
    exit;
}

// --- CONTROLEER VOORWAARDEN ---
if (!class_exists('Imagick')) {
    die("<h1>Fout: De Imagick PHP-extensie is niet geïnstalleerd!</h1>");
}
if (!is_dir(IMAGE_DIR_PROCESSED)) {
    mkdir(IMAGE_DIR_PROCESSED, 0755, true);
}

// --- FUNCTIE: GEMINI API AANROEPEN ---
function getGeminiDescription($filepath) {
    if (!defined('GEMINI_API_KEY') || empty(GEMINI_API_KEY)) return null;

    $base64 = base64_encode(file_get_contents($filepath));
    $mime = mime_content_type($filepath);
    
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent?key=" . GEMINI_API_KEY;
    
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
    $response = curl_exec($ch);
    curl_close($ch);

    $json = json_decode($response, true);
    if (isset($json['candidates'][0]['content']['parts'][0]['text'])) {
        $text = trim($json['candidates'][0]['content']['parts'][0]['text']);
        // Maak bestandsnaam-veilig: vervang spaties door streepjes, verwijder rare tekens
        $clean = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $text));
        return substr($clean, 0, 60);
    }
    return null;
}

// --- AJAX VERWERKING (1 FOTO PER REQUEST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'process_image') {
    header('Content-Type: application/json');
    $original_file = $_POST['file'] ?? '';
    $prefix = $_POST['prefix'] ?? '0000';

    if (!$original_file || !file_exists($original_file)) {
        echo json_encode(['success' => false, 'error' => 'Origineel bestand niet gevonden']);
        exit;
    }

    try {
        // 1. Haal EXIF datum op (indien beschikbaar)
        $exif = @exif_read_data($original_file);
        $date_str = '';
        if ($exif && isset($exif['DateTimeOriginal'])) {
            $date_str = date('Ymd_Hi', strtotime($exif['DateTimeOriginal'])) . '_';
        }

        // 2. Vraag nieuwe naam aan Gemini (met fallback)
        $ai_name = getGeminiDescription($original_file);
        if (!$ai_name) {
            $ai_name = pathinfo($original_file, PATHINFO_FILENAME); // Fallback naar origineel
        }

        $new_filename = $prefix . '_' . $date_str . $ai_name . '.webp';
        $output_path = IMAGE_DIR_PROCESSED . '/' . $new_filename;

        if (!file_exists($output_path)) {
            $image = new Imagick();
            $image->readImage($original_file);
            $image->resizeImage(WEBP_MAX_WIDTH, WEBP_MAX_HEIGHT, Imagick::FILTER_LANCZOS, 1, true);
            $image->stripImage(); // Verwijdert originele EXIF in de WebP voor bestandsgrootte

            $draw = new ImagickDraw();
            $draw->setFont(WATERMARK_FONT);
            $draw->setFontSize(WATERMARK_FONT_SIZE);
            $draw->setFillColor(new ImagickPixel(WATERMARK_FILL_COLOR));
            $draw->setStrokeColor(new ImagickPixel(WATERMARK_STROKE_COLOR));
            $draw->setStrokeWidth(1);
            $draw->setGravity(Imagick::GRAVITY_CENTER);
            $image->annotateImage($draw, 0, 0, WATERMARK_ANGLE, WATERMARK_TEXT);

            $image->setImageFormat('webp');
            $image->setOption('webp:method', '6');
            $image->setImageCompressionQuality(WEBP_QUALITY);
            $image->writeImage($output_path);

            $image->clear();
            $image->destroy();
            
            echo json_encode(['success' => true, 'new_name' => $new_filename]);
        } else {
            echo json_encode(['success' => true, 'message' => 'Bestand bestond al']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// --- BEREID LIJST MET BESTANDEN VOOR ---
$originals = glob(IMAGE_DIR_ORIGINAL . '/*.{jpg,jpeg,png}', GLOB_BRACE);
$image_data = [];
foreach ($originals as $original_file) {
    $exif = @exif_read_data($original_file);
    $exif_date = ($exif && isset($exif['DateTimeOriginal'])) ? strtotime($exif['DateTimeOriginal']) : filemtime($original_file);
    $image_data[$original_file] = $exif_date;
}
asort($image_data);

// Bepaal onverwerkte bestanden (checkt op basis van prefix om dubbel werk te voorkomen)
$pending_files = [];
$counter = 0;
$processed_files = glob(IMAGE_DIR_PROCESSED . '/*.webp');
$processed_prefixes = array_map(function($path) {
    return substr(basename($path), 0, 4);
}, $processed_files);

foreach ($image_data as $original_file => $timestamp) {
    $prefix = sprintf('%04d', $counter);
    if (!in_array($prefix, $processed_prefixes)) {
        $pending_files[] = [
            'original' => $original_file,
            'prefix' => $prefix
        ];
    }
    $counter++;
}

require_once 'templates/header.php';
?>

<?php if (!empty($pending_files)): ?>
    <div id="progress-container" style="background: #333; color: #fff; padding: 20px; margin: 0 auto 30px auto; max-width: 900px; border-radius: 8px;">
        <h2>Bezig met AI-verwerking van <span id="pending-count"><?php echo count($pending_files); ?></span> foto's...</h2>
        <p>Let op: Om API-limieten te respecteren zit er een vertraging tussen foto's. Sluit de browser niet af.</p>
        <progress id="conversion-progress" value="0" max="<?php echo count($pending_files); ?>" style="width: 100%; height: 20px;"></progress>
        <div id="conversion-log" style="font-family: monospace; font-size: 13px; margin-top: 15px; height: 150px; overflow-y: auto; background: #222; padding: 10px; border: 1px solid #555;"></div>
    </div>

    <script>
        const pendingFiles = <?php echo json_encode($pending_files); ?>;
        let currentIndex = 0;
        const logDiv = document.getElementById('conversion-log');
        const progress = document.getElementById('conversion-progress');
        const pendingCount = document.getElementById('pending-count');

        function logMessage(msg) {
            logDiv.innerHTML += msg + "<br>";
            logDiv.scrollTop = logDiv.scrollHeight;
        }

        async function processNext() {
            if (currentIndex >= pendingFiles.length) {
                logMessage("✅ Proces voltooid. De pagina wordt vernieuwd...");
                setTimeout(() => window.location.reload(), 2000);
                return;
            }

            const fileData = pendingFiles[currentIndex];
            const displayFileName = fileData.original.split('/').pop();
            logMessage("Analyseer & verwerk " + displayFileName + " ...");

            const formData = new FormData();
            formData.append('action', 'process_image');
            formData.append('file', fileData.original);
            formData.append('prefix', fileData.prefix);

            try {
                const response = await fetch(window.location.href, { method: 'POST', body: formData });
                const result = await response.json();

                if (result.success) {
                    logMessage("  -> ✅ Opgeslagen als: " + result.new_name);
                } else {
                    logMessage("  -> ❌ Fout: " + (result.error || "Onbekend probleem"));
                }
            } catch (error) {
                logMessage("  -> ❌ Netwerkfout. Mogelijke time-out.");
            }

            currentIndex++;
            progress.value = currentIndex;
            pendingCount.innerText = (pendingFiles.length - currentIndex);
            
            // Respecteer API rate limits door 4 seconden te wachten voor de volgende call
            setTimeout(processNext, 4000); 
        }

        document.addEventListener('DOMContentLoaded', processNext);
    </script>
<?php endif; ?>

<?php
require_once 'templates/description.php';
$gallery_images = glob(IMAGE_DIR_PROCESSED . '/*.webp');
sort($gallery_images, SORT_STRING);

// Implement Infinite Scroll logic
$initial_count = 20; // Number of images to render initially
$initial_images = array_slice($gallery_images, 0, $initial_count);

?>

<!-- Inject the gallery images into a JavaScript variable for the infinite scroller -->
<script>
    window.galleryImages = <?php echo json_encode($gallery_images); ?>;
    window.initialImageCount = <?php echo count($initial_images); ?>;
</script>

<main>
    <div class="gallery-container">
        <div class="gallery-grid" id="gallery-grid">
            <?php foreach ($initial_images as $image_path): ?>
                <div class="gallery-item gallery-clickable" data-full="<?php echo htmlspecialchars($image_path); ?>">
                    <img src="<?php echo htmlspecialchars($image_path); ?>" alt="<?php echo htmlspecialchars(basename($image_path, '.webp')); ?>" loading="lazy">
                    <div class="gallery-item-title" style="white-space: normal; line-height: 1.2; padding: 5px;">
                        <?php echo htmlspecialchars(substr(basename($image_path, '.webp'), 5)); // Verberg de prefix ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</main>

<div class="lightbox-overlay">
    <div class="lightbox-top-bar">
        <span class="lightbox-nav lightbox-prev">&#10094;</span>
        <span class="lightbox-nav lightbox-next">&#10095;</span>
        <span class="lightbox-close">&times;</span>
    </div>
    <div class="lightbox-content">
        <img src="" alt="" id="lightbox-img">
        <div class="lightbox-title" id="lightbox-title"></div>
    </div>
</div>

<?php require_once 'templates/footer.php'; ?>