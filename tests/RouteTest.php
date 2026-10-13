<?php

use Illuminate\Support\Facades\Route;
use Saade\Facehash\Facades\Facehash;

beforeEach(fn () => $this->bootWithConfig(['route.enabled' => true]));

it('serves the avatar as a cacheable svg', function () {
    $response = $this->get('/facehash?name=Saade')->assertOk();

    expect($response->getContent())->toBe(Facehash::name('Saade')->toSvg())
        ->and($response->headers->get('Content-Type'))->toBe('image/svg+xml')
        ->and($response->headers->getCacheControlDirective('max-age'))->toBe('31536000')
        ->and($response->headers->hasCacheControlDirective('public'))->toBeTrue()
        ->and($response->headers->hasCacheControlDirective('immutable'))->toBeTrue();
});

it('sets no cookies on the cacheable response', function () {
    expect($this->get('/facehash?name=Saade')->headers->getCookies())->toBeEmpty()
        ->and(Route::getRoutes()->getByName('facehash')->gatherMiddleware())->toBe([]);
});

it('names the route', function () {
    expect(route('facehash', ['name' => 'Saade'], absolute: false))->toBe('/facehash?name=Saade');
});

it('applies every query parameter', function () {
    $query = http_build_query([
        'name' => 'alice@example.com',
        'size' => 128,
        'variant' => 'solid',
        'format' => 'squircle',
        'initial' => 'false',
        'blink' => 'true',
        'colors' => ['#6366f1', '#8b5cf6', '#a78bfa'],
    ]);

    $expected = Facehash::name('alice@example.com')
        ->size(128)
        ->variant('solid')
        ->format('squircle')
        ->initial(false)
        ->blink()
        ->colors(['#6366f1', '#8b5cf6', '#a78bfa'])
        ->toSvg();

    expect($this->get("/facehash?{$query}")->assertOk()->getContent())->toBe($expected);
});

it('reads boolean query parameters', function (string $value, bool $shown) {
    $svg = $this->get("/facehash?name=Saade&initial={$value}")->assertOk()->getContent();

    expect(str_contains($svg, '<text'))->toBe($shown);
})->with([
    ['true', true],
    ['1', true],
    ['on', true],
    ['false', false],
    ['0', false],
    ['off', false],
]);

it('accepts sizes at the edges of the allowed range', function (int $size) {
    $this->get("/facehash?name=Saade&size={$size}")->assertOk();
})->with([16, 1024]);

it('rejects invalid input', function (array $query, string $field) {
    $this->getJson('/facehash?' . http_build_query($query))
        ->assertUnprocessable()
        ->assertJsonValidationErrors($field);
})->with([
    'missing name' => [[], 'name'],
    'empty name' => [['name' => ''], 'name'],
    'name as a list' => [['name' => ['Saade']], 'name'],
    'name over 255 characters' => [['name' => str_repeat('a', 256)], 'name'],
    'size below the minimum' => [['name' => 'Saade', 'size' => 15], 'size'],
    'size above the maximum' => [['name' => 'Saade', 'size' => 1025], 'size'],
    'size that is not a number' => [['name' => 'Saade', 'size' => 'big'], 'size'],
    'unknown variant' => [['name' => 'Saade', 'variant' => 'holographic'], 'variant'],
    'unknown format' => [['name' => 'Saade', 'format' => 'hexagon'], 'format'],
    'colors that is not a list' => [['name' => 'Saade', 'colors' => '#ffffff'], 'colors'],
    'color that is not hex' => [['name' => 'Saade', 'colors' => ['red']], 'colors.0'],
    'color that breaks out of the attribute' => [['name' => 'Saade', 'colors' => ['#fff"/><script>']], 'colors.0'],
]);

it('uses the configured defaults for parameters that are not given', function () {
    $this->bootWithConfig(['route.enabled' => true, 'defaults.size' => 64]);

    expect($this->get('/facehash?name=Saade')->getContent())->toContain('width="64"');
});

it('serves the route under the configured prefix', function () {
    $this->bootWithConfig(['route.enabled' => true, 'route.prefix' => 'avatars/face']);

    $this->get('/avatars/face?name=Saade')->assertOk();
    $this->get('/facehash?name=Saade')->assertNotFound();
});

it('applies the configured middleware', function () {
    $this->bootWithConfig(['route.enabled' => true, 'route.middleware' => ['api']]);

    expect(Route::getRoutes()->getByName('facehash')->gatherMiddleware())->toBe(['api']);
});

it('sends the configured cache-control header', function () {
    $this->bootWithConfig(['route.enabled' => true, 'route.cache_control' => 'no-store']);

    expect($this->get('/facehash?name=Saade')->headers->get('Cache-Control'))->toContain('no-store');
});
