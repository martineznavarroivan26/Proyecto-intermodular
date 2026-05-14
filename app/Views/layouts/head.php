<?php
declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="shortcut icon" href="<?= asset('estilos/src/Inazuma_Eleven_Logo_1.webp') ?>" type="image/x-icon">
    <link rel="preload" as="image" href="<?= htmlspecialchars(asset('estilos/src/cursor.png'), ENT_QUOTES, 'UTF-8') ?>">
    <style>
        :root {
            --asset-background-url: url("<?= htmlspecialchars(asset('estilos/src/Background.gif'), ENT_QUOTES, 'UTF-8') ?>");
            --asset-cursor-url: url("<?= htmlspecialchars(asset('estilos/src/cursor.png'), ENT_QUOTES, 'UTF-8') ?>");
        }
    </style>
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
<?php foreach ($styles as $style) : ?>
    <link rel="stylesheet" href="<?= asset($style) ?>">
<?php endforeach; ?>
</head>
<body>