<?php

use Saade\Facehash\Data\FaceSvgData;
use Saade\Facehash\Enums\FaceType;
use Saade\Facehash\Enums\Format;
use Saade\Facehash\Enums\Variant;
use Saade\Facehash\Facades\Facehash;

// Expected values come from running the upstream JavaScript stringHash() over
// each name, not from this package, so a drift in the PHP port fails here.
it('picks the face, colour and gaze the reference hash picks', function (string $name, FaceType $face, string $color, array $gaze) {
    expect(Facehash::name($name)->toSvg())
        ->toContain('viewBox="' . FaceSvgData::get($face)['viewBox'] . '"')
        ->toContain('fill="' . $color . '"');

    expect(gaze($name))->toBe($gaze);
})->with([
    'round' => ['Saade', FaceType::Round, '#f97316', [-1, 1]],
    'cross' => ['a', FaceType::Cross, '#3b82f6', [-1, 1]],
    'line' => ['&co', FaceType::Line, '#f97316', [-1, -1]],
    'curved' => ['facehash', FaceType::Curved, '#ec4899', [1, -1]],
    'hash near the 32-bit ceiling' => ['alice@example.com', FaceType::Cross, '#f59e0b', [-1, 1]],
    'hash that wraps negative' => ['John Doe', FaceType::Curved, '#3b82f6', [1, 0]],
    'digits that wrap negative' => ['1234567890', FaceType::Cross, '#10b981', [-1, -1]],
    'long name' => ['A very long display name with spaces', FaceType::Cross, '#f59e0b', [0, 1]],
    'accented latin' => ['Zoë', FaceType::Line, '#f59e0b', [0, 1]],
    'cjk' => ['日本語', FaceType::Curved, '#f97316', [-1, -1]],
    'surrogate pair' => ['😀 smile', FaceType::Cross, '#ec4899', [1, 1]],
]);

it('renders the same avatar for the same name every time', function () {
    expect(Facehash::name('alice@example.com')->toSvg())
        ->toBe(Facehash::name('alice@example.com')->toSvg());
});

it('treats names as case sensitive', function () {
    expect(Facehash::name('Saade')->toSvg())->not->toBe(Facehash::name('saade')->toSvg());
});

it('refuses to render without a name', function () {
    Facehash::toSvg();
})->throws(InvalidArgumentException::class, 'Name is required');

it('refuses to render an empty name', function () {
    Facehash::name('')->toSvg();
})->throws(InvalidArgumentException::class, 'Name is required');

it('returns a new instance from every builder method', function (string $method, mixed $argument) {
    $original = Facehash::name('Saade');
    $before = $original->toSvg();

    $configured = $original->{$method}($argument);

    expect($configured)->not->toBe($original)
        ->and($configured->toSvg())->not->toBe($before)
        ->and($original->toSvg())->toBe($before);
})->with([
    'name' => ['name', 'Someone else'],
    'size' => ['size', 128],
    'variant' => ['variant', 'solid'],
    'format' => ['format', 'square'],
    'blink' => ['blink', true],
    'initial' => ['initial', false],
    'colors' => ['colors', ['#000000']],
]);

it('picks the colour from a custom palette by hash', function () {
    // 'a' hashes to 97, and 97 % 3 === 1.
    $svg = Facehash::name('a')->colors(['#111111', '#222222', '#333333'])->toSvg();

    expect($svg)->toContain('fill="#222222"')
        ->not->toContain('#111111')
        ->not->toContain('#333333');
});

it('always uses the only colour of a one-colour palette', function (string $name) {
    expect(Facehash::name($name)->colors(['#abcdef'])->toSvg())->toContain('fill="#abcdef"');
})->with(['Saade', 'a', 'John Doe', 'Zoë']);

it('falls back to the default palette for an empty one', function () {
    expect(Facehash::name('Saade')->colors(['#000000'])->colors([])->toSvg())
        ->toBe(Facehash::name('Saade')->toSvg());
});

it('accepts the variant as an enum or its string value', function () {
    expect(Facehash::name('Saade')->variant(Variant::Solid)->toSvg())
        ->toBe(Facehash::name('Saade')->variant('solid')->toSvg());
});

it('accepts the format as an enum or its string value', function () {
    expect(Facehash::name('Saade')->format(Format::Squircle)->toSvg())
        ->toBe(Facehash::name('Saade')->format('squircle')->toSvg());
});

it('falls back to the gradient variant for an unknown string', function () {
    expect(Facehash::name('Saade')->variant('solid')->variant('holographic')->toSvg())
        ->toBe(Facehash::name('Saade')->variant(Variant::Gradient)->toSvg());
});

it('falls back to the circle format for an unknown string', function () {
    expect(Facehash::name('Saade')->format('square')->format('hexagon')->toSvg())
        ->toBe(Facehash::name('Saade')->format(Format::Circle)->toSvg());
});

it('encodes the svg as base64', function () {
    $facehash = Facehash::name('Saade');

    expect(base64_decode($facehash->toBase64(), strict: true))->toBe($facehash->toSvg());
});

it('builds a data uri around the base64 svg', function () {
    $facehash = Facehash::name('Saade');

    expect($facehash->toUri())->toBe('data:image/svg+xml;base64,' . $facehash->toBase64());
});
