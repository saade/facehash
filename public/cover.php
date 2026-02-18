<?php

/**
 * Facehash Social Media Cover
 *
 * php -S localhost:8080 cover.php
 */

require_once __DIR__ . '/../src/Enums/FaceType.php';
require_once __DIR__ . '/../src/Enums/Format.php';
require_once __DIR__ . '/../src/Enums/Variant.php';
require_once __DIR__ . '/../src/Data/FacehashData.php';
require_once __DIR__ . '/../src/Data/FaceSvgData.php';
require_once __DIR__ . '/../src/Support/SvgRenderer.php';
require_once __DIR__ . '/../src/Facehash.php';

use Saade\Facehash\Facehash;

$facehash = new Facehash;

// ---------------------------------------------------------------------------
// Avatar positions (percentages) — scattered around the perimeter
// ---------------------------------------------------------------------------

$formats = ['square', 'squircle', 'circle'];
$variants = ['gradient', 'solid'];

$avatars = [
    // Top edge
    ['name' => 'alice',   'x' => 12, 'y' => 15],
    ['name' => 'bob',     'x' => 25, 'y' => 10],
    ['name' => 'charlie', 'x' => 38, 'y' => 16],
    ['name' => 'diana',   'x' => 56, 'y' => 11],
    ['name' => 'eve',     'x' => 68, 'y' => 15],
    ['name' => 'frank',   'x' => 82, 'y' => 10],

    // Left edge
    ['name' => 'grace',   'x' => 10, 'y' => 38],
    ['name' => 'henry',   'x' => 12, 'y' => 55],

    // Right edge
    ['name' => 'ivy',     'x' => 82, 'y' => 40],
    ['name' => 'jack',    'x' => 84, 'y' => 57],

    // Bottom edge
    ['name' => 'kate',    'x' => 10, 'y' => 74],
    ['name' => 'leo',     'x' => 24, 'y' => 78],
    ['name' => 'mia',     'x' => 38, 'y' => 73],
    ['name' => 'noah',    'x' => 56, 'y' => 78],
    ['name' => 'olivia',  'x' => 70, 'y' => 74],
    ['name' => 'paul',    'x' => 83, 'y' => 78],
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Facehash Cover</title>
<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400&display=swap');

    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    html, body {
        height: 100%;
    }

    body {
        background: #0a0a0a;
        overflow: hidden;
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .cover {
        position: relative;
        width: 1500px;
        height: 788px;
        background: #0a0a0a;
        overflow: hidden;
        margin: 0 auto;
    }

    /* Subtle radial glow behind center text */
    .cover::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 700px;
        height: 500px;
        transform: translate(-50%, -50%);
        background: radial-gradient(ellipse, rgba(255,255,255,0.02) 0%, transparent 70%);
        pointer-events: none;
    }

    /* Center text */
    .title {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -55%);
        text-align: center;
        z-index: 10;
    }

    .title h1 {
        font-size: 72px;
        font-weight: 300;
        letter-spacing: 0.35em;
        color: rgba(255, 255, 255, 0.9);
        text-transform: uppercase;
        margin: 0;
        line-height: 1;
    }

    .title p {
        margin-top: 20px;
        font-size: 22px;
        font-weight: 300;
        color: rgba(255, 255, 255, 0.35);
        letter-spacing: 0.04em;
    }

    /* Avatar cards */
    .card {
        position: absolute;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 6px;
    }

    .card svg {
        filter: drop-shadow(0 4px 24px rgba(0, 0, 0, 0.5));
    }

    .name {
        font-size: 12px;
        color: rgba(255, 255, 255, 0.4);
        letter-spacing: 0.03em;
    }
</style>
</head>
<body>
<div class="cover">

    <div class="title">
        <h1>Facehash</h1>
        <p>beautiful minimalist avatars from any string for Laravel</p>
    </div>

    <?php foreach ($avatars as $i => $a):
        $hash = crc32($a['name']);
        $fmt = $formats[abs($hash) % count($formats)];
        $var = $variants[abs($hash >> 2) % count($variants)];
        $tilt = (($hash % 21) - 10) * 0.8;
    ?>
        <div style="position:absolute;left:<?= $a['x'] ?>%;top:<?= $a['y'] ?>%;">
            <div class="card" style="transform:rotate(<?= $tilt ?>deg)">
                <?= $facehash->name($a['name'])->size(70)->format($fmt)->variant($var)->blink()->toSvg() ?>
                <span class="name"><?= htmlspecialchars($a['name']) ?></span>
            </div>
        </div>
    <?php endforeach; ?>

</div>
</body>
</html>
