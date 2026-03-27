<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo PAGE_TITLE; ?></title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        /* Fallback for BG_COLOR if variables aren't supported */
        body { background-color: <?php echo BG_COLOR; ?>; }
        /* Using the CSS variable defined in style.css */
        :root { --bg-color: <?php echo BG_COLOR; ?>; }
    </style>
</head>
<body>
    <div class="theme-toggle-container">
        <button id="theme-toggle">🌙 Dark Mode</button>
    </div>
    <h1><?php echo PAGE_TITLE; ?></h1>
