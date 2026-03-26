<?php
// Laad de configuratie
require_once 'config.php';

// --- CONTROLEER VOORWAARDEN ---
// Check nu of de Imagick-extensie bestaat.
if (!class_exists('Imagick')) {
    die("<h1>Fout: De Imagick PHP-extensie is niet geïnstalleerd!</h1><p>Installeer deze met 'sudo apt-get install php-imagick' en herstart je webserver.</p>");
}
if (!is_dir(IMAGE_DIR_PROCESSED)) {
    mkdir(IMAGE_DIR_PROCESSED, 0755, true);
}

// --- SORTEER ORIGINELE BESTANDEN OP BASIS VAN EXIF-DATUM ---
$originals = glob(IMAGE_DIR_ORIGINAL . '/*.{jpg,jpeg,png}', GLOB_BRACE);
$image_data = [];
foreach ($originals as $original_file) {
    $exif_date = null;
    if (in_array(strtolower(pathinfo($original_file, PATHINFO_EXTENSION)), ['jpg', 'jpeg'])) {
        $exif = @exif_read_data($original_file);
        if ($exif && isset($exif['DateTimeOriginal'])) {
            $exif_date = strtotime($exif['DateTimeOriginal']);
        }
    }
    $image_data[$original_file] = $exif_date ?: filemtime($original_file);
}
asort($image_data);

// --- START DE NIEUWE CONVERSIE-LOGICA MET PHP IMAGICK ---
$conversion_needed = false;
$conversion_output = '';
$counter = 0;

foreach ($image_data as $original_file => $timestamp) {
    $prefix = sprintf('%04d', $counter);
    $original_basename = pathinfo($original_file, PATHINFO_FILENAME);
    $new_filename = $prefix . '_' . $original_basename . '.webp';
    $output_path = IMAGE_DIR_PROCESSED . '/' . $new_filename;

    if (!file_exists($output_path)) {
        $conversion_needed = true;
        $conversion_output .= "Verwerk: " . basename($original_file) . " -> " . basename($output_path) . "\n";

        try {
            // Maak een nieuw Imagick object aan
            $image = new Imagick();
            $image->readImage($original_file);

            // 1. Verklein de afbeelding (alleen als deze groter is dan 4K)
            $image->resizeImage(WEBP_MAX_WIDTH, WEBP_MAX_HEIGHT, Imagick::FILTER_LANCZOS, 1, true);

            // 2. Verwijder metadata
            $image->stripImage();

            // 3. Voeg het watermerk toe
            $draw = new ImagickDraw();
            $draw->setFont(WATERMARK_FONT);
            $draw->setFontSize(WATERMARK_FONT_SIZE);
            $draw->setFillColor(new ImagickPixel(WATERMARK_FILL_COLOR));
            $draw->setStrokeColor(new ImagickPixel(WATERMARK_STROKE_COLOR));
            $draw->setStrokeWidth(1);
            $draw->setGravity(Imagick::GRAVITY_CENTER);
            $image->annotateImage($draw, 0, 0, WATERMARK_ANGLE, WATERMARK_TEXT);

            // 4. Stel WebP formaat en compressie in
            $image->setImageFormat('webp');
            $image->setOption('webp:method', '6');
            $image->setImageCompressionQuality(WEBP_QUALITY);

            // 5. Schrijf het bestand weg
            $image->writeImage($output_path);

            // 6. Ruim geheugen op
            $image->clear();
            $image->destroy();
            $conversion_output .= "  -> ✅ SUCCES\n";

        } catch (Exception $e) {
            $conversion_output .= "  -> ❌ FOUT: " . $e->getMessage() . "\n";
        }
    }
    $counter++;
}

// --- PAGINA OPBOUW ---
require_once 'templates/header.php';
if ($conversion_needed) {
    echo "<div style='font-family: monospace; background: #333; color: #fff; padding: 20px; margin: 0 auto 30px auto; max-width: 900px; border-radius: 8px;'>";
    echo "<h1>Conversie voltooid</h1><p>De volgende acties zijn uitgevoerd:</p><pre>" . htmlspecialchars($conversion_output) . "</pre></div>";
}
require_once 'templates/description.php';
$gallery_images = glob(IMAGE_DIR_PROCESSED . '/*.webp');
sort($gallery_images, SORT_STRING);
?>

<main>
    <div class="gallery-container">
        <div class="gallery-grid">
            <?php if (empty($gallery_images)): ?>
                <p>Geen afbeeldingen gevonden. Plaats .jpg of .png bestanden in de map '<?php echo IMAGE_DIR_ORIGINAL; ?>' en herlaad de pagina.</p>
            <?php else: ?>
                <?php foreach ($gallery_images as $image_path): ?>
                    <div class="gallery-item gallery-clickable" data-full="<?php echo htmlspecialchars($image_path); ?>">
                        <img src="<?php echo htmlspecialchars($image_path); ?>" alt="<?php echo htmlspecialchars(basename($image_path)); ?>" loading="lazy">
                        <div class="gallery-item-title"><?php echo htmlspecialchars(basename($image_path)); ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
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
<?php
require_once 'templates/footer.php';
?>
