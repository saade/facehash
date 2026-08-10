<?php

/**
 * Docs page template. Rendered to _site/index.html by docs/build.php, which
 * provides $facehash, $formats, $variants, $faceTypes, $defaults and $colors.
 *
 * @var \Saade\Facehash\Facehash $facehash
 * @var list<string> $formats
 * @var list<string> $variants
 * @var list<string> $faceTypes
 * @var array $defaults
 * @var list<string> $colors
 * @var string $siteUrl
 */

/** Highlight a PHP snippet with token_get_all() — no build-time dependencies. */
$php = function (string $code): string {
    $tokens = token_get_all("<?php\n" . trim($code));
    array_shift($tokens);

    $html = '';

    foreach ($tokens as $token) {
        if (is_string($token)) {
            $html .= htmlspecialchars($token);

            continue;
        }

        [$id, $text] = $token;

        $class = match (true) {
            in_array($id, [T_COMMENT, T_DOC_COMMENT], true) => 'tok-comment',
            in_array($id, [T_CONSTANT_ENCAPSED_STRING, T_ENCAPSED_AND_WHITESPACE, T_LNUMBER, T_DNUMBER], true) => 'tok-string',
            $id === T_VARIABLE => 'tok-var',
            $id === T_WHITESPACE, $id === T_INLINE_HTML => null,
            in_array($id, [T_STRING, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED], true) => 'tok-fn',
            default => 'tok-keyword',
        };

        $escaped = htmlspecialchars($text);
        $html .= $class ? '<span class="' . $class . '">' . $escaped . '</span>' : $escaped;
    }

    return '<pre><code>' . $html . '</code></pre>';
};

/** A plain, unhighlighted code block for shell/Blade/HTML snippets. */
$plain = fn (string $code): string => '<pre><code>' . htmlspecialchars(trim($code)) . '</code></pre>';

$heroNames = ['Ada', 'Linus', 'Grace', 'Taylor', 'Nikola', 'Saade', 'Rasmus', 'Marie'];
$galleryNames = ['alice', 'bob', 'charlie', 'diana', 'eve', 'frank', 'grace', 'hank', 'ivy', 'jack', 'karen', 'leo', 'mona', 'nora', 'oscar', 'paul'];

$toc = [
    'installation' => 'Installation',
    'quick-start' => 'Quick start',
    'builder' => 'Builder API',
    'output' => 'Output methods',
    'blade' => 'Blade usage',
    'route' => 'HTTP route',
    'configuration' => 'Configuration',
    'how-it-works' => 'How it works',
    'credits' => 'Credits',
];

$repo = 'https://github.com/saade/facehash';

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Facehash for Laravel — deterministic SVG avatars from any string</title>
<meta name="description" content="Deterministic avatar faces from any string. Pure SVG output, no GD or Imagick, with a Laravel-native builder API.">
<meta name="theme-color" content="#0a0a0a">
<link rel="canonical" href="<?= $siteUrl ?>/">
<meta property="og:title" content="Facehash for Laravel">
<meta property="og:description" content="Deterministic avatar faces from any string. Pure SVG output, no GD or Imagick.">
<meta property="og:image" content="<?= $siteUrl ?>/assets/cover.png">
<meta property="og:url" content="<?= $siteUrl ?>/">
<meta property="og:type" content="website">
<meta name="twitter:card" content="summary_large_image">
<link rel="icon" href="<?= $facehash->name('Facehash')->size(64)->toUri() ?>">
<link rel="stylesheet" href="assets/site.css">
</head>
<body>

<header class="topbar">
    <div class="wrap">
        <a class="brand" href="#top">
            <?= $facehash->name('Facehash')->size(26)->format('squircle')->toSvg() ?>
            Facehash
        </a>
        <nav>
            <a href="#playground">Playground</a>
            <a href="#showcase">Showcase</a>
            <a href="#docs">Docs</a>
            <a class="gh" href="<?= $repo ?>">GitHub&nbsp;&rarr;</a>
        </nav>
    </div>
</header>

<main id="top">

<section class="hero">
    <div class="wrap">
        <div class="hero-strip">
            <?php foreach ($heroNames as $i => $name): ?>
                <?= $facehash->name($name)->size(56)->format($formats[$i % count($formats)])->blink()->toSvg() ?>
            <?php endforeach; ?>
        </div>

        <h1>Deterministic avatars<br>from any string</h1>
        <p class="tagline">
            A name, an email, an ID — Facehash turns it into the same friendly SVG face every time.
            No GD, no Imagick, no external services.
        </p>

        <div class="hero-actions">
            <button class="copy" type="button" data-copy="composer require saade/facehash">
                <span class="prompt">$</span>
                <span>composer require saade/facehash</span>
                <span class="hint">copy</span>
            </button>
            <a class="button" href="#docs">Read the docs</a>
            <a class="button" href="<?= $repo ?>">GitHub</a>
        </div>
    </div>
</section>

<section id="playground">
    <div class="wrap">
        <div class="section-head">
            <span class="eyebrow">Playground</span>
            <h2>Try it with your own name</h2>
            <p>Rendered in your browser by a port of the same renderer the package ships.</p>
        </div>

        <div class="playground">
            <div class="controls">
                <div class="control wide">
                    <label for="pg-name">Name</label>
                    <input type="text" id="pg-name" value="Saade" autocomplete="off" spellcheck="false">
                </div>
                <div class="control">
                    <label for="pg-size">Size</label>
                    <input type="number" id="pg-size" value="160" min="16" max="512" step="8">
                </div>
                <div class="control">
                    <label for="pg-variant">Variant</label>
                    <select id="pg-variant">
                        <?php foreach ($variants as $variant): ?>
                            <option value="<?= $variant ?>"<?= $variant === $defaults['variant'] ? ' selected' : '' ?>><?= $variant ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="control">
                    <label for="pg-format">Format</label>
                    <select id="pg-format">
                        <?php foreach ($formats as $format): ?>
                            <option value="<?= $format ?>"<?= $format === $defaults['format'] ? ' selected' : '' ?>><?= $format ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="control wide">
                    <label for="pg-colors">Palette <span style="text-transform:none;letter-spacing:0">(comma separated, blank for default)</span></label>
                    <input type="text" id="pg-colors" placeholder="<?= implode(', ', array_slice($colors, 0, 3)) ?>" autocomplete="off" spellcheck="false">
                </div>
                <div class="toggles">
                    <label class="toggle"><input type="checkbox" id="pg-initial"<?= $defaults['initial'] ? ' checked' : '' ?>> Initial</label>
                    <label class="toggle"><input type="checkbox" id="pg-blink" checked> Blink</label>
                </div>
            </div>

            <div class="stage">
                <div id="pg-preview"></div>
                <div class="stage-meta" id="pg-meta"></div>
            </div>
        </div>

        <div class="snippet" id="pg-snippet"></div>
    </div>
</section>

<section id="showcase">
    <div class="wrap">
        <div class="section-head">
            <span class="eyebrow">Showcase</span>
            <h2>Every knob, pre-rendered</h2>
            <p>All of the SVG below was generated at build time by the PHP renderer.</p>
        </div>

        <div class="showcase">
            <div>
                <h3>Format</h3>
                <p><code>square</code>, <code>squircle</code> or <code>circle</code></p>
                <div class="row">
                    <?php foreach ($formats as $format): ?>
                        <div class="tile">
                            <?= $facehash->name('Saade')->size(64)->format($format)->toSvg() ?>
                            <span><?= $format ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div>
                <h3>Variant</h3>
                <p>Radial highlight or flat fill</p>
                <div class="row">
                    <?php foreach ($variants as $variant): ?>
                        <div class="tile">
                            <?= $facehash->name('Saade')->size(64)->variant($variant)->toSvg() ?>
                            <span><?= $variant ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div>
                <h3>Initial</h3>
                <p>First letter of the name, below the eyes</p>
                <div class="row">
                    <?php foreach ([true, false] as $showInitial): ?>
                        <div class="tile">
                            <?= $facehash->name('Saade')->size(64)->initial($showInitial)->toSvg() ?>
                            <span><?= $showInitial ? 'on' : 'off' ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div>
                <h3>Blink</h3>
                <p>CSS animation embedded in the SVG</p>
                <div class="row">
                    <?php foreach ([true, false] as $blink): ?>
                        <div class="tile">
                            <?= $facehash->name('Saade')->size(64)->blink($blink)->toSvg() ?>
                            <span><?= $blink ? 'on' : 'off' ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div>
                <h3>Size</h3>
                <p>Geometry scales with the pixel size</p>
                <div class="row">
                    <?php foreach ([24, 32, 48, 64] as $size): ?>
                        <div class="tile">
                            <?= $facehash->name('Saade')->size($size)->toSvg() ?>
                            <span><?= $size ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div>
                <h3>Palette</h3>
                <p>Any list of colors you like</p>
                <div class="row">
                    <?php
                    $palettes = [
                        ['#6366f1', '#8b5cf6', '#a78bfa'],
                        ['#ef4444', '#f97316', '#eab308'],
                        ['#14b8a6', '#06b6d4', '#3b82f6'],
                    ];
                    foreach ($palettes as $i => $palette): ?>
                        <div class="tile">
                            <?= $facehash->name('Saade')->size(64)->colors($palette)->toSvg() ?>
                            <span>#<?= $i + 1 ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="gallery">
    <div class="wrap">
        <div class="section-head">
            <span class="eyebrow">Gallery</span>
            <h2><?= count($faceTypes) ?> face types &times; <?= count($colors) ?> colors &times; 9 tilts</h2>
            <p><?= count($faceTypes) * count($colors) * 9 ?> distinct faces from the default palette — before you add colors of your own.</p>
        </div>

        <div class="grid">
            <?php foreach ($galleryNames as $name): ?>
                <div class="tile">
                    <?= $facehash->name($name)->size(64)->format('squircle')->blink()->toSvg() ?>
                    <span><?= htmlspecialchars($name) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section id="docs">
    <div class="wrap docs">
        <nav class="toc" aria-label="Documentation">
            <?php foreach ($toc as $id => $label): ?>
                <a href="#<?= $id ?>"><?= $label ?></a>
            <?php endforeach; ?>
        </nav>

        <div class="prose">
            <h2 id="installation">Installation</h2>
            <p>Requires PHP 8.2+ and Laravel 11, 12 or 13.</p>
            <?= $plain('composer require saade/facehash') ?>
            <p>The service provider and the <code>Facehash</code> facade are auto-discovered — there is nothing to register.</p>

            <h2 id="quick-start">Quick start</h2>
            <?= $php(<<<'PHP'
                use Saade\Facehash\Facades\Facehash;

                // Generate an SVG string
                $svg = Facehash::name('Saade')->toSvg();

                // Embed as a data URI in an <img> tag
                $uri = Facehash::name('Saade')->toUri();
                PHP) ?>

            <h2 id="builder">Builder API</h2>
            <p>Every method returns a new instance, so you can keep a configured builder around and branch off it without side effects.</p>

            <h3><code>name(string $name)</code></h3>
            <p><strong>Required.</strong> The input string. The same string always produces the same face.</p>
            <?= $php("Facehash::name('alice@example.com')->toSvg();") ?>

            <h3><code>size(int $pixels)</code></h3>
            <p>Avatar dimensions in pixels. Default: <code><?= $defaults['size'] ?></code>.</p>
            <?= $php("Facehash::name('Saade')->size(128)->toSvg();") ?>

            <h3><code>variant(string $variant)</code></h3>
            <p>Background style — <code>'gradient'</code> or <code>'solid'</code>. Default: <code>'<?= $defaults['variant'] ?>'</code>.</p>
            <?= $php("Facehash::name('Saade')->variant('solid')->toSvg();") ?>

            <h3><code>format(string $format)</code></h3>
            <p>Avatar shape — <code>'square'</code>, <code>'squircle'</code> or <code>'circle'</code>. Default: <code>'<?= $defaults['format'] ?>'</code>.</p>
            <?= $php("Facehash::name('Saade')->format('squircle')->toSvg();") ?>

            <h3><code>blink(bool $enable = true)</code></h3>
            <p>Adds a CSS blink animation to the eyes, inlined in the SVG. Default: <code><?= $defaults['blink'] ? 'true' : 'false' ?></code>.</p>
            <?= $php("Facehash::name('Saade')->blink()->toSvg();") ?>

            <h3><code>initial(bool $show = true)</code></h3>
            <p>Show the first letter of the name below the eyes. Default: <code><?= $defaults['initial'] ? 'true' : 'false' ?></code>.</p>
            <?= $php("Facehash::name('Saade')->initial(false)->toSvg();") ?>

            <h3><code>colors(array $colors)</code></h3>
            <p>Override the palette. Each avatar deterministically picks one color from the list.</p>
            <?= $php("Facehash::name('Saade')->colors(['#6366f1', '#8b5cf6', '#a78bfa'])->toSvg();") ?>

            <h2 id="output">Output methods</h2>
            <table>
                <thead>
                    <tr><th>Method</th><th>Returns</th></tr>
                </thead>
                <tbody>
                    <tr><td><code>toSvg()</code></td><td>Raw SVG string</td></tr>
                    <tr><td><code>toBase64()</code></td><td>Base64-encoded SVG</td></tr>
                    <tr><td><code>toUri()</code></td><td>Data URI (<code>data:image/svg+xml;base64,&hellip;</code>)</td></tr>
                </tbody>
            </table>

            <h2 id="blade">Blade usage</h2>
            <?= $plain(<<<'BLADE'
                {{-- Inline SVG --}}
                {!! Facehash::name($user->name)->size(48)->toSvg() !!}

                {{-- As <img> src --}}
                <img src="{{ Facehash::name($user->name)->size(48)->toUri() }}" alt="{{ $user->name }}">

                {{-- Via the route --}}
                <img src="{{ route('facehash', ['name' => $user->name, 'size' => 48]) }}" alt="{{ $user->name }}">
                BLADE) ?>

            <h2 id="route">HTTP route</h2>
            <p>
                The package ships an optional <code>GET</code> endpoint that returns an <code>image/svg+xml</code> response with
                long-lived cache headers. It is <strong>disabled by default</strong>.
            </p>
            <?= $php(<<<'PHP'
                // config/facehash.php
                'route' => [
                    'enabled' => true,
                ],
                PHP) ?>
            <?= $plain('GET /facehash?name=Saade') ?>

            <h3>Query parameters</h3>
            <table>
                <thead>
                    <tr><th>Parameter</th><th>Type</th><th>Default</th><th>Description</th></tr>
                </thead>
                <tbody>
                    <tr><td><code>name</code></td><td>string</td><td><em>required</em></td><td>Input string</td></tr>
                    <tr><td><code>size</code></td><td>int</td><td><code><?= $defaults['size'] ?></code></td><td>Size in pixels (16&ndash;1024)</td></tr>
                    <tr><td><code>variant</code></td><td>string</td><td><code><?= $defaults['variant'] ?></code></td><td><?= implode(' or ', array_map(fn ($v) => "<code>$v</code>", $variants)) ?></td></tr>
                    <tr><td><code>format</code></td><td>string</td><td><code><?= $defaults['format'] ?></code></td><td><?= implode(', ', array_map(fn ($f) => "<code>$f</code>", $formats)) ?></td></tr>
                    <tr><td><code>initial</code></td><td>bool</td><td><code><?= $defaults['initial'] ? 'true' : 'false' ?></code></td><td>Show initial letter</td></tr>
                    <tr><td><code>blink</code></td><td>bool</td><td><code><?= $defaults['blink'] ? 'true' : 'false' ?></code></td><td>Enable blink animation</td></tr>
                    <tr><td><code>colors[]</code></td><td>string[]</td><td>&mdash;</td><td>Custom hex palette</td></tr>
                </tbody>
            </table>

            <?= $plain(<<<'HTML'
                <img src="/facehash?name=Saade" alt="Avatar">
                <img src="/facehash?name=Saade&size=128&variant=solid" alt="Avatar">
                <img src="/facehash?name=Saade&colors[]=%236366f1&colors[]=%238b5cf6" alt="Avatar">
                HTML) ?>

            <h2 id="configuration">Configuration</h2>
            <?= $plain('php artisan vendor:publish --tag=facehash-config') ?>
            <?= $php(file_get_contents(dirname(__DIR__) . '/config/facehash.php')) ?>
            <ul>
                <li><strong>defaults</strong> &mdash; starting values for the builder; any method call overrides them per instance.</li>
                <li><strong>colors</strong> &mdash; the palette each avatar picks from. The shipped default uses Tailwind's 500-weight colors.</li>
                <li><strong>route.enabled</strong> &mdash; register the HTTP endpoint. Off by default.</li>
                <li><strong>route.cache_control</strong> &mdash; the default caches for a year as immutable, which is safe because the same input always produces the same bytes.</li>
            </ul>

            <h2 id="how-it-works">How it works</h2>
            <ol>
                <li>The input string is hashed to a deterministic 32-bit integer.</li>
                <li>The hash picks a <strong>face type</strong>, a <strong>color</strong> from the palette, and one of nine <strong>tilts</strong> that offset the face for a subtle 3D look.</li>
                <li>The renderer composites the clip path, background, gradient overlay, eye paths and the initial letter into a single SVG.</li>
                <li>The same string always produces the exact same SVG &mdash; across requests, servers and deploys.</li>
            </ol>

            <h3>Face types</h3>
            <div class="row" style="display:flex;gap:1.5rem;flex-wrap:wrap;margin-bottom:1.5rem;">
                <?php
                // Search for a name that hashes to each face type so the docs show all of them.
                foreach ($faceTypes as $index => $faceType):
                    $sample = 'A';

                    foreach (range('a', 'z') as $letter) {
                        $hash = 0;

                        foreach (str_split($letter) as $char) {
                            $hash = (($hash << 5) - $hash + ord($char)) & 0xFFFFFFFF;
                        }

                        if ($hash % count($faceTypes) === $index) {
                            $sample = $letter;

                            break;
                        }
                    }
                    ?>
                    <div class="tile">
                        <?= $facehash->name($sample)->size(64)->initial(false)->toSvg() ?>
                        <span><?= $faceType ?></span>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="note">
                The hash is a direct port of the JavaScript original, so a given string yields the same face in PHP as it does in the JS library.
            </div>

            <h2 id="credits">Credits</h2>
            <p>
                A PHP/Laravel port of <a href="https://facehash.dev">facehash</a> by
                <a href="https://github.com/cossistantcom/cossistant">Cossistant</a>, which provides the face paths, hash algorithm
                and rendering logic this package reproduces. Released under the MIT license.
            </p>
        </div>
    </div>
</section>

</main>

<footer class="wrap">
    <span>MIT &copy; <a href="https://github.com/saade">Saade</a></span>
    <span><a href="<?= $repo ?>">Source on GitHub</a></span>
</footer>

<script type="module">
import { createFacehash } from './assets/facehash.js';
import data from './assets/facehash-data.js';

const facehash = createFacehash(data);
const defaults = data.defaults;

const field = (id) => document.getElementById(id);
const inputs = {
    name: field('pg-name'),
    size: field('pg-size'),
    variant: field('pg-variant'),
    format: field('pg-format'),
    colors: field('pg-colors'),
    initial: field('pg-initial'),
    blink: field('pg-blink'),
};

const preview = field('pg-preview');
const meta = field('pg-meta');
const snippet = field('pg-snippet');

const escape = (value) => value.replace(/[&<>]/g, (char) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;' })[char]);
const quote = (value) => `'${value.replace(/\\/g, '\\\\').replace(/'/g, "\\'")}'`;

function currentOptions() {
    const size = Number.parseInt(inputs.size.value, 10);
    const colors = inputs.colors.value.split(',').map((color) => color.trim()).filter(Boolean);

    return {
        name: inputs.name.value.trim() || 'Saade',
        size: Number.isFinite(size) ? Math.min(Math.max(size, 16), 512) : defaults.size,
        variant: inputs.variant.value,
        format: inputs.format.value,
        initial: inputs.initial.checked,
        blink: inputs.blink.checked,
        colors,
    };
}

function buildSnippet(options) {
    const calls = [`name(${quote(options.name)})`];

    if (options.size !== defaults.size) calls.push(`size(${options.size})`);
    if (options.variant !== defaults.variant) calls.push(`variant(${quote(options.variant)})`);
    if (options.format !== defaults.format) calls.push(`format(${quote(options.format)})`);
    if (options.initial !== defaults.initial) calls.push(`initial(${options.initial ? 'true' : 'false'})`);
    if (options.blink !== defaults.blink) calls.push(`blink(${options.blink ? 'true' : 'false'})`);
    if (options.colors.length) calls.push(`colors([${options.colors.map(quote).join(', ')}])`);

    calls.push('toSvg()');

    const chain = calls.join('\n    ->');

    return `<pre><code><span class="tok-fn">Facehash</span>::${escape(chain).replace(/-&gt;(\w+)/g, '-&gt;<span class="tok-fn">$1</span>')};</code></pre>`;
}

function render() {
    const options = currentOptions();
    const svg = facehash.toSvg(options);

    preview.innerHTML = svg;
    meta.textContent = `${new Blob([svg]).size} bytes of SVG`;
    snippet.innerHTML = buildSnippet(options);
}

Object.values(inputs).forEach((input) => {
    input.addEventListener('input', render);
    input.addEventListener('change', render);
});

render();
</script>

<script>
// Copy-to-clipboard for the install command.
document.querySelectorAll('.copy').forEach((button) => {
    button.addEventListener('click', async () => {
        const hint = button.querySelector('.hint');

        try {
            await navigator.clipboard.writeText(button.dataset.copy);
            hint.textContent = 'copied';
        } catch {
            hint.textContent = 'press ⌘C';
        }

        setTimeout(() => { hint.textContent = 'copy'; }, 1600);
    });
});

// Highlight the docs section currently in view.
const links = new Map([...document.querySelectorAll('.toc a')].map((link) => [link.hash.slice(1), link]));
const headings = [...document.querySelectorAll('.prose h2[id]')];

if (headings.length) {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (!entry.isIntersecting) return;
            links.forEach((link) => link.classList.remove('active'));
            links.get(entry.target.id)?.classList.add('active');
        });
    }, { rootMargin: '-70px 0px -75% 0px' });

    headings.forEach((heading) => observer.observe(heading));
}
</script>

</body>
</html>
