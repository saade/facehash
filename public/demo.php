<?php

/**
 * Facehash Demo Page
 *
 * php -S localhost:8080 demo.php
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

// Handle /avatar endpoint — returns raw SVG
if (str_starts_with($_SERVER['REQUEST_URI'], '/avatar')) {
    parse_str($_SERVER['QUERY_STRING'] ?? '', $params);

    $name = $params['name'] ?? '';
    if ($name === '') {
        http_response_code(400);
        echo 'Missing ?name= parameter';
        exit;
    }

    $builder = $facehash->name($name);

    if (isset($params['size'])) {
        $builder = $builder->size((int) $params['size']);
    }
    if (isset($params['variant'])) {
        $builder = $builder->variant($params['variant']);
    }
    if (isset($params['blink'])) {
        $builder = $builder->blink($params['blink'] !== '0' && $params['blink'] !== 'false');
    }
    if (isset($params['format'])) {
        $builder = $builder->format($params['format']);
    }
    if (isset($params['initial'])) {
        $builder = $builder->initial($params['initial'] !== '0' && $params['initial'] !== 'false');
    }

    header('Content-Type: image/svg+xml');
    header('Cache-Control: public, max-age=31536000, immutable');
    echo $builder->toSvg();
    exit;
}

// Demo page
$names = ['Saade', 'Alice', 'Bob', 'Charlie', 'Diana', 'Eve', 'Frank', 'Grace', 'Hank', 'Ivy', 'Jack', 'Karen', 'Leo', 'Mona', 'Nora', 'Oscar'];

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Facehash Demo</title>
<style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #0a0a0a; color: #e5e5e5; padding: 3rem 1.5rem; }
    .container { max-width: 900px; margin: 0 auto; }
    h1 { font-size: 2rem; font-weight: 700; margin-bottom: 0.25rem; }
    .subtitle { color: #737373; margin-bottom: 2.5rem; }
    h2 { font-size: 1.25rem; font-weight: 600; margin-bottom: 1rem; color: #d4d4d4; }
    section { margin-bottom: 3rem; }
    .grid { display: flex; flex-wrap: wrap; gap: 1.25rem; }
    .card { display: flex; flex-direction: column; align-items: center; gap: 0.5rem; }
    .card span { font-size: 0.75rem; color: #a3a3a3; }
    .row { display: flex; align-items: center; gap: 1.5rem; flex-wrap: wrap; }
    .playground { background: #171717; border: 1px solid #262626; border-radius: 0.75rem; padding: 1.5rem; }
    .controls { display: flex; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem; }
    .control { display: flex; flex-direction: column; gap: 0.25rem; }
    .control label { font-size: 0.75rem; color: #a3a3a3; text-transform: uppercase; letter-spacing: 0.05em; }
    .control input, .control select { background: #262626; border: 1px solid #404040; color: #e5e5e5; padding: 0.5rem 0.75rem; border-radius: 0.375rem; font-size: 0.875rem; }
    .control input:focus, .control select:focus { outline: none; border-color: #ec4899; }
    .preview { display: flex; align-items: center; justify-content: center; gap: 2rem; min-height: 180px; }
    .preview-svg { }
    code { background: #262626; padding: 0.125rem 0.5rem; border-radius: 0.25rem; font-size: 0.8rem; color: #ec4899; }
    .face-types { display: flex; gap: 1.25rem; flex-wrap: wrap; }
</style>
</head>
<body>
<div class="container">

<h1>Facehash</h1>
<p class="subtitle">Deterministic avatar faces from any string</p>

<!-- Grid of avatars -->
<section>
    <h2>Gallery</h2>
    <?php foreach (['square', 'squircle', 'circle'] as $fmt): ?>
    <div style="margin-bottom: 0.5rem;">
        <span style="font-size: 0.75rem; color: #737373; text-transform: uppercase; letter-spacing: 0.05em;"><?= $fmt ?></span>
    </div>
    <div class="grid" style="margin-bottom: 1.5rem;">
        <?php foreach ($names as $name): ?>
        <div class="card">
            <?php echo $facehash->name($name)->size(64)->format($fmt)->blink()->toSvg(); ?>
            <span><?= htmlspecialchars($name) ?></span>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endforeach; ?>
</section>

<!-- Face types -->
<section>
    <h2>Face Types</h2>
    <div class="face-types">
        <?php
        // Pick names that deterministically produce each face type
        $faceTypeNames = ['Saade' => 'round', 'Bob' => 'cross', 'Alice' => 'line', 'Charlie' => 'curved'];
        // Actually generate each to find which face type they get, then show all 4
        $shown = [];
        foreach (['round', 'cross', 'line', 'curved'] as $ft) {
            // Find a name that hashes to this face type
            foreach (array_merge($names, range('A', 'Z')) as $candidate) {
                $n = is_int($candidate) ? chr($candidate + 65) : $candidate;
                $svg = $facehash->name($n)->size(80)->variant('gradient')->toSvg();
                // Quick check — we can't easily extract face type from outside, so just show varied names
            }
        }
        // Simpler approach: just show 4 diverse names at large size
        $showcase = ['Saade', 'Eve', 'Frank', 'Diana'];
        foreach ($showcase as $n):
        ?>
        <div class="card">
            <?php echo $facehash->name($n)->size(80)->toSvg(); ?>
            <span><?= htmlspecialchars($n) ?></span>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- Sizes -->
<section>
    <h2>Sizes</h2>
    <div class="row">
        <?php foreach ([24, 32, 40, 48, 64, 96, 128] as $s): ?>
        <div class="card">
            <?php echo $facehash->name('Saade')->size($s)->toSvg(); ?>
            <span><?= $s ?>px</span>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- Variants -->
<section>
    <h2>Variants</h2>
    <div class="row">
        <div class="card">
            <?php echo $facehash->name('Saade')->size(80)->variant('gradient')->toSvg(); ?>
            <span>gradient</span>
        </div>
        <div class="card">
            <?php echo $facehash->name('Saade')->size(80)->variant('solid')->toSvg(); ?>
            <span>solid</span>
        </div>
    </div>
</section>

<!-- Format -->
<section>
    <h2>Format</h2>
    <div class="row">
        <?php foreach (['square', 'squircle', 'circle'] as $fmt): ?>
        <div class="card">
            <?php echo $facehash->name('Saade')->size(80)->format($fmt)->toSvg(); ?>
            <span><?= $fmt ?></span>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- Blink -->
<section>
    <h2>Blink Animation</h2>
    <div class="row">
        <div class="card">
            <?php echo $facehash->name('Saade')->size(96)->blink()->toSvg(); ?>
            <span>blink: on</span>
        </div>
        <div class="card">
            <?php echo $facehash->name('Saade')->size(96)->blink(false)->toSvg(); ?>
            <span>blink: off</span>
        </div>
    </div>
</section>

<!-- Initial toggle -->
<section>
    <h2>Initial Letter</h2>
    <div class="row">
        <div class="card">
            <?php echo $facehash->name('Saade')->size(80)->initial(true)->toSvg(); ?>
            <span>initial: on</span>
        </div>
        <div class="card">
            <?php echo $facehash->name('Saade')->size(80)->initial(false)->toSvg(); ?>
            <span>initial: off</span>
        </div>
    </div>
</section>

<!-- Custom colors -->
<section>
    <h2>Custom Colors</h2>
    <div class="row">
        <?php
        $palettes = [
            ['#6366f1', '#8b5cf6', '#a78bfa'],
            ['#ef4444', '#f97316', '#eab308'],
            ['#14b8a6', '#06b6d4', '#3b82f6'],
        ];
        foreach ($palettes as $palette):
        ?>
        <div class="card">
            <?php echo $facehash->name('Saade')->size(80)->colors($palette)->toSvg(); ?>
            <span><?= implode(' ', $palette) ?></span>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- Interactive playground -->
<section>
    <h2>Playground</h2>
    <div class="playground">
        <div class="controls">
            <div class="control">
                <label>Name</label>
                <input type="text" id="pg-name" value="Saade" oninput="updatePlayground()">
            </div>
            <div class="control">
                <label>Size</label>
                <input type="number" id="pg-size" value="128" min="16" max="512" oninput="updatePlayground()">
            </div>
            <div class="control">
                <label>Variant</label>
                <select id="pg-variant" onchange="updatePlayground()">
                    <option value="gradient">gradient</option>
                    <option value="solid">solid</option>
                </select>
            </div>
            <div class="control">
                <label>Format</label>
                <select id="pg-format" onchange="updatePlayground()">
                    <option value="square">square</option>
                    <option value="squircle">squircle</option>
                    <option value="circle">circle</option>
                </select>
            </div>
            <div class="control">
                <label>Blink</label>
                <select id="pg-blink" onchange="updatePlayground()">
                    <option value="1">on</option>
                    <option value="0">off</option>
                </select>
            </div>
            <div class="control">
                <label>Initial</label>
                <select id="pg-initial" onchange="updatePlayground()">
                    <option value="1">on</option>
                    <option value="0">off</option>
                </select>
            </div>
        </div>
        <div class="preview" id="pg-preview">
            <img id="pg-img" class="preview-svg" src="/avatar?name=Saade&size=128&blink=1" width="128" height="128">
        </div>
    </div>
</section>

<!-- Route endpoint info -->
<section>
    <h2>Raw SVG Endpoint</h2>
    <p style="color:#a3a3a3; font-size:0.875rem; line-height:1.75;">
        <code>GET /avatar?name=Saade&amp;size=128&amp;variant=gradient&amp;blink=1&amp;initial=1</code>
        <br>Returns <code>image/svg+xml</code> response.
    </p>
</section>

</div>

<script>
function updatePlayground() {
    const name = document.getElementById('pg-name').value || 'Saade';
    const size = document.getElementById('pg-size').value || '128';
    const variant = document.getElementById('pg-variant').value;
    const format = document.getElementById('pg-format').value;
    const blink = document.getElementById('pg-blink').value;
    const initial = document.getElementById('pg-initial').value;
    const img = document.getElementById('pg-img');
    const params = new URLSearchParams({ name, size, variant, format, blink, initial });
    img.src = '/avatar?' + params.toString();
    img.width = size;
    img.height = size;
}
</script>
</body>
</html>
<?php
