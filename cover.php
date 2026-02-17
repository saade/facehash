<?php

/**
 * Facehash Social Media Cover
 *
 * php -S localhost:8080 cover.php
 */

require_once __DIR__ . '/src/Enums/FaceType.php';
require_once __DIR__ . '/src/Enums/Variant.php';
require_once __DIR__ . '/src/Data/FacehashData.php';
require_once __DIR__ . '/src/Data/FaceSvgData.php';
require_once __DIR__ . '/src/Support/SvgRenderer.php';
require_once __DIR__ . '/src/Facehash.php';

use Saade\Facehash\Data\FacehashData;
use Saade\Facehash\Data\FaceSvgData;
use Saade\Facehash\Enums\FaceType;

// ---------------------------------------------------------------------------
// Hash + compute helpers (mirrors Facehash internals)
// ---------------------------------------------------------------------------

function stringHash(string $str): int
{
    $hash = 0;
    for ($i = 0, $len = strlen($str); $i < $len; $i++) {
        $hash = (($hash << 5) - $hash + ord($str[$i])) & 0xFFFFFFFF;
    }
    if ($hash >= 0x80000000) {
        $hash -= 0x100000000;
    }
    return abs($hash);
}

function computeFace(string $name): array
{
    $colors = ['#ec4899', '#f59e0b', '#3b82f6', '#f97316', '#10b981'];
    $positions = [
        ['x' => -1, 'y' => 1], ['x' => 1, 'y' => 1], ['x' => 1, 'y' => 0],
        ['x' => 0, 'y' => 1], ['x' => -1, 'y' => 0], ['x' => 0, 'y' => 0],
        ['x' => 0, 'y' => -1], ['x' => -1, 'y' => -1], ['x' => 1, 'y' => -1],
    ];
    $faceTypes = FaceType::cases();

    $hash = stringHash($name);

    $faceType = $faceTypes[$hash % count($faceTypes)];
    $color = $colors[$hash % count($colors)];
    $rotation = $positions[$hash % count($positions)];
    $initial = mb_strtoupper(mb_substr($name, 0, 1));
    $tilt = (($hash % 21) - 10) * 0.8; // -8 to +8 degrees

    return compact('faceType', 'color', 'rotation', 'initial', 'tilt');
}

// ---------------------------------------------------------------------------
// Render a single square avatar as inline HTML
// ---------------------------------------------------------------------------

function renderSquareAvatar(string $name, int $size = 70): string
{
    $data = computeFace($name);
    $svgData = FaceSvgData::get($data['faceType']);
    $viewBox = $svgData['viewBox'];
    $paths = $svgData['paths'];

    $vbParts = explode(' ', $viewBox);
    $vbW = (float) $vbParts[2];
    $vbH = (float) $vbParts[3];
    $aspect = $vbW / $vbH;

    $faceW = $size * 0.55;
    $faceH = $faceW / $aspect;
    $fontSize = $size * 0.28;

    $offsetMag = $size * 0.04;
    $ox = $data['rotation']['y'] * $offsetMag;
    $oy = -$data['rotation']['x'] * $offsetMag;

    $radius = $size * 0.18;
    $c = $data['color'];
    $tilt = $data['tilt'];

    $pathsHtml = '';
    foreach ($paths as $d) {
        $pathsHtml .= '<path d="' . $d . '" fill="rgba(0,0,0,0.75)"/>';
    }

    return <<<HTML
    <div class="card" style="transform:rotate({$tilt}deg)">
        <div class="avatar" style="width:{$size}px;height:{$size}px;background:{$c};border-radius:{$radius}px;">
            <div class="gradient-overlay" style="border-radius:{$radius}px;"></div>
            <div class="face" style="margin-left:{$ox}px;margin-top:{$oy}px;">
                <svg viewBox="{$viewBox}" width="{$faceW}" height="{$faceH}" fill="none" xmlns="http://www.w3.org/2000/svg">{$pathsHtml}</svg>
                <span class="initial" style="font-size:{$fontSize}px;">{$data['initial']}</span>
            </div>
        </div>
        <span class="name">{$name}</span>
    </div>
    HTML;
}

// ---------------------------------------------------------------------------
// Avatar positions (percentages) — scattered around the perimeter
// ---------------------------------------------------------------------------

$avatars = [
    // Top edge
    ['name' => 'alice',   'x' => 5,  'y' => 5],
    ['name' => 'bob',     'x' => 17, 'y' => 1],
    ['name' => 'charlie', 'x' => 31, 'y' => 7],
    ['name' => 'diana',   'x' => 64, 'y' => 2],
    ['name' => 'eve',     'x' => 80, 'y' => 5],
    ['name' => 'frank',   'x' => 93, 'y' => 1],

    // Left edge
    ['name' => 'grace',   'x' => 1,  'y' => 32],
    ['name' => 'henry',   'x' => 4,  'y' => 53],

    // Right edge
    ['name' => 'ivy',     'x' => 91, 'y' => 35],
    ['name' => 'jack',    'x' => 93, 'y' => 55],

    // Bottom edge
    ['name' => 'kate',    'x' => 3,  'y' => 78],
    ['name' => 'leo',     'x' => 18, 'y' => 84],
    ['name' => 'mia',     'x' => 34, 'y' => 78],
    ['name' => 'noah',    'x' => 62, 'y' => 83],
    ['name' => 'olivia',  'x' => 78, 'y' => 80],
    ['name' => 'paul',    'x' => 93, 'y' => 84],
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
        transition: transform 0.3s ease;
    }

    .avatar {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        box-shadow: 0 4px 24px rgba(0, 0, 0, 0.5);
    }

    .gradient-overlay {
        position: absolute;
        inset: 0;
        background: radial-gradient(ellipse 100% 100% at 30% 30%, rgba(255,255,255,0.12) 0%, transparent 60%);
        pointer-events: none;
    }

    .face {
        position: relative;
        display: flex;
        flex-direction: column;
        align-items: center;
        z-index: 1;
    }

    .initial {
        margin-top: 4px;
        font-family: monospace;
        font-weight: 700;
        line-height: 1;
        color: rgba(0, 0, 0, 0.75);
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

    <?php foreach ($avatars as $a): ?>
        <div style="position:absolute;left:<?= $a['x'] ?>%;top:<?= $a['y'] ?>%;">
            <?= renderSquareAvatar($a['name']) ?>
        </div>
    <?php endforeach; ?>

</div>
</body>
</html>
