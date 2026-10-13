<?php

use Saade\Facehash\Data\FaceSvgData;
use Saade\Facehash\Enums\FaceType;
use Saade\Facehash\Facades\Facehash;

it('renders at the requested size', function () {
    expect(Facehash::name('Saade')->size(128)->toSvg())
        ->toStartWith('<svg xmlns="http://www.w3.org/2000/svg" width="128" height="128" viewBox="0 0 128 128"');
});

it('clips and fills the avatar with the shape of its format', function (string $format, int $size, string $shape) {
    $svg = Facehash::name('Saade')->format($format)->size($size)->variant('solid')->toSvg();

    expect($svg)
        ->toMatch('/<clipPath id="[^"]+">' . preg_quote("<{$shape}/>", '/') . '<\/clipPath>/')
        ->toContain("<{$shape} fill=\"#f97316\"/>");
})->with([
    'circle' => ['circle', 40, 'circle cx="20" cy="20" r="20"'],
    'circle at an odd size' => ['circle', 63, 'circle cx="31.5" cy="31.5" r="31.5"'],
    'square' => ['square', 40, 'rect width="40" height="40"'],
    'squircle' => ['squircle', 100, 'rect width="100" height="100" rx="22"'],
    'squircle rounds its radius' => ['squircle', 40, 'rect width="40" height="40" rx="9"'],
]);

it('lays a radial highlight over the gradient variant', function () {
    $svg = Facehash::name('Saade')->variant('gradient')->toSvg();

    expect($svg)->toContain('<radialGradient')
        ->toMatch('/<circle cx="20" cy="20" r="20" fill="url\(#grad-[^)]+\)"\/>/');
});

it('leaves the solid variant flat', function () {
    expect(Facehash::name('Saade')->variant('solid')->toSvg())
        ->not->toContain('radialGradient')
        ->not->toContain('url(#grad-');
});

it('only references ids it defines', function (string $variant, string $format) {
    $svg = Facehash::name('Saade')->variant($variant)->format($format)->toSvg();

    preg_match_all('/url\(#([^)]+)\)/', $svg, $references);
    preg_match_all('/ id="([^"]+)"/', $svg, $definitions);

    expect($references[1])->not->toBeEmpty()
        ->and(array_diff($references[1], $definitions[1]))->toBeEmpty();
})->with(['gradient', 'solid'])->with(['circle', 'square', 'squircle']);

it('draws every eye path of the face in black', function (string $name, FaceType $face) {
    $svg = Facehash::name($name)->toSvg();
    $paths = FaceSvgData::get($face)['paths'];

    foreach ($paths as $path) {
        expect($svg)->toContain('<path d="' . $path . '" fill="black"/>');
    }

    expect(substr_count($svg, '<path '))->toBe(count($paths));
})->with([
    'round' => ['Saade', FaceType::Round],
    'cross' => ['a', FaceType::Cross],
    'line' => ['&co', FaceType::Line],
    'curved' => ['facehash', FaceType::Curved],
]);

it('sizes the face to 60% of the avatar and keeps its aspect ratio', function () {
    // 'a' is a cross face, whose artwork is 71 by 23.
    preg_match('/<svg x="[^"]+" y="[^"]+" width="([^"]+)" height="([^"]+)"/', Facehash::name('a')->size(100)->toSvg(), $face);

    expect((float) $face[1])->toBe(60.0)
        ->and((float) $face[2])->toEqualWithDelta(60 * 23 / 71, 0.0001);
});

it('prints the uppercased first character of the name', function (string $name, string $initial) {
    expect(Facehash::name($name)->toSvg())->toContain('>' . $initial . '</text>');
})->with([
    'ascii' => ['saade', 'S'],
    'already uppercase' => ['Saade', 'S'],
    'digit' => ['1234567890', '1'],
    'accented' => ['élan', 'É'],
    'cjk' => ['日本語', '日'],
    'emoji' => ['😀 smile', '😀'],
]);

it('escapes an initial that is markup', function (string $name, string $escaped) {
    expect(Facehash::name($name)->toSvg())->toContain('>' . $escaped . '</text>');
})->with([
    'tag' => ['<script>', '&lt;'],
    'ampersand' => ['&co', '&amp;'],
]);

it('scales the initial with the avatar', function () {
    expect(Facehash::name('Saade')->size(100)->toSvg())
        ->toContain('font-size="26"')
        ->toContain('text-anchor="middle"');
});

it('omits the initial when asked to', function () {
    expect(Facehash::name('Saade')->initial(false)->toSvg())->not->toContain('<text');
});

it('moves the face up to make room for the initial', function () {
    $faceY = function (string $svg): float {
        preg_match('/<svg x="[^"]+" y="([^"]+)"/', $svg, $face);

        return (float) $face[1];
    };

    expect($faceY(Facehash::name('Saade')->toSvg()))
        ->toBeLessThan($faceY(Facehash::name('Saade')->initial(false)->toSvg()));
});

it('blinks each eye as a whole', function (string $name, FaceType $face) {
    $svg = Facehash::name($name)->blink()->toSvg();
    $middle = explode(' ', FaceSvgData::get($face)['viewBox'])[2] / 2;

    preg_match_all('/<path d="M([\d.]+)[^"]*" fill="black" class="fh-eye-(left|right)"\/>/', $svg, $paths, PREG_SET_ORDER);

    expect($svg)
        ->toContain('@keyframes facehash-blink-left')
        ->toContain('@keyframes facehash-blink-right')
        ->and($paths)->toHaveCount(substr_count($svg, '<path '));

    foreach ($paths as [, $startX, $eye]) {
        expect($eye)->toBe($startX < $middle ? 'left' : 'right');
    }
})->with([
    'round' => ['Saade', FaceType::Round],
    'cross' => ['a', FaceType::Cross],
    'line' => ['&co', FaceType::Line],
    'curved' => ['facehash', FaceType::Curved],
]);

it('ships no animation unless blinking is enabled', function () {
    expect(Facehash::name('Saade')->toSvg())
        ->not->toContain('<style>')
        ->not->toContain('class=');
});

it('is well-formed xml', function (string $name, string $variant, string $format, bool $blink) {
    $svg = Facehash::name($name)->variant($variant)->format($format)->blink($blink)->toSvg();

    expect((new DOMDocument)->loadXML($svg))->toBeTrue();
})
    ->with([
        'plain' => 'Saade',
        'tag' => '<script>',
        'ampersand' => '&co',
        'double quote' => '"quoted"',
        'single quote' => "it's",
        'cjk' => '日本語',
        'emoji' => '😀',
        'invalid utf-8' => "\xFFabc",
    ])
    ->with(['gradient', 'solid'])
    ->with(['circle', 'square', 'squircle'])
    ->with([true, false]);

// One test per snapshot rather than a dataset: Pest 3 and Pest 4 name dataset
// snapshots differently, and the suite has to pass on both.
it('keeps the markup of a round face', function () {
    expect(Facehash::name('Saade')->toSvg())->toMatchSnapshot();
});

it('keeps the markup of a cross face', function () {
    expect(Facehash::name('a')->toSvg())->toMatchSnapshot();
});

it('keeps the markup of a line face', function () {
    expect(Facehash::name('&co')->toSvg())->toMatchSnapshot();
});

it('keeps the markup of a curved face', function () {
    expect(Facehash::name('facehash')->toSvg())->toMatchSnapshot();
});

it('keeps the markup of a solid squircle without an initial', function () {
    expect(Facehash::name('Saade')->size(128)->variant('solid')->format('squircle')->initial(false)->toSvg())
        ->toMatchSnapshot();
});

it('keeps the markup of a blinking square with a custom palette', function () {
    expect(Facehash::name('alice@example.com')->size(63)->format('square')->blink()->colors(['#6366f1', '#8b5cf6', '#a78bfa'])->toSvg())
        ->toMatchSnapshot();
});
