<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Saade\Facehash\Facades\Facehash;
use Saade\Facehash\Facehash as FacehashBuilder;
use Saade\Facehash\FacehashServiceProvider;

it('merges the package config', function () {
    expect(config('facehash.defaults'))->toBe([
        'size' => 40,
        'variant' => 'gradient',
        'format' => 'circle',
        'initial' => true,
        'blink' => false,
    ])->and(config('facehash.colors'))->toHaveCount(5);
});

it('offers the config file for publishing', function () {
    $paths = ServiceProvider::pathsToPublish(FacehashServiceProvider::class, 'facehash-config');

    expect($paths)->toHaveCount(1)
        ->and(realpath(array_key_first($paths)))->toBe(realpath(__DIR__ . '/../config/facehash.php'))
        ->and(array_values($paths))->toBe([config_path('facehash.php')]);
});

it('resolves the builder as a singleton that the facade proxies to', function () {
    expect(app(FacehashBuilder::class))->toBe(app(FacehashBuilder::class))
        ->and(Facehash::getFacadeRoot())->toBe(app(FacehashBuilder::class));
});

it('never mutates the shared builder', function () {
    Facehash::name('Saade')->size(512)->variant('solid')->format('square')->blink()->initial(false)->colors(['#000000']);

    expect(Facehash::name('Saade')->toSvg())->toBe((new FacehashBuilder)->name('Saade')->toSvg());
});

it('takes its defaults from the config', function () {
    $this->bootWithConfig([
        'defaults' => ['size' => 64, 'variant' => 'solid', 'format' => 'square', 'initial' => false, 'blink' => true],
        'colors' => ['#123456'],
    ]);

    expect(Facehash::name('Saade')->toSvg())
        ->toContain('<rect width="64" height="64" fill="#123456"/>')
        ->toContain('<style>')
        ->not->toContain('radialGradient')
        ->not->toContain('<text');
});

it('lets a builder call override a configured default', function () {
    $this->bootWithConfig(['defaults.size' => 64]);

    expect(Facehash::name('Saade')->size(24)->toSvg())->toContain('width="24"');
});

it('falls back to the shipped defaults for keys missing from the config', function () {
    $expected = Facehash::name('Saade')->toSvg();

    config()->set('facehash', []);

    expect((new FacehashBuilder)->name('Saade')->toSvg())->toBe($expected);
});

it('falls back to the shipped palette when the configured one is empty', function () {
    $expected = Facehash::name('Saade')->toSvg();

    $this->bootWithConfig(['colors' => []]);

    expect(Facehash::name('Saade')->toSvg())->toBe($expected);
});

it('uses the configured palette when a builder is given an empty one', function () {
    $this->bootWithConfig(['colors' => ['#123456']]);

    expect(Facehash::name('Saade')->colors([])->toSvg())->toContain('fill="#123456"');
});

it('falls back to the shipped defaults for an unknown configured variant or format', function () {
    $expected = Facehash::name('Saade')->toSvg();

    $this->bootWithConfig(['defaults.variant' => 'holographic', 'defaults.format' => 'hexagon']);

    expect(Facehash::name('Saade')->toSvg())->toBe($expected);
});

it('does not register the route unless it is enabled', function () {
    expect(Route::has('facehash'))->toBeFalse();

    $this->get('/facehash?name=Saade')->assertNotFound();
});
