<?php

/**
 * Renders a matrix of options through the PHP renderer.
 *
 * Reads a JSON array of option objects on stdin, writes a JSON array of SVG
 * strings on stdout. Driven by docs/parity.mjs — see that file.
 */

declare(strict_types=1);

$root = dirname(__DIR__);

require_once $root . '/src/Enums/FaceType.php';
require_once $root . '/src/Enums/Format.php';
require_once $root . '/src/Enums/Variant.php';
require_once $root . '/src/Data/FacehashData.php';
require_once $root . '/src/Data/FaceSvgData.php';
require_once $root . '/src/Support/SvgRenderer.php';
require_once $root . '/src/Facehash.php';

use Saade\Facehash\Facehash;

$cases = json_decode(stream_get_contents(STDIN), true, flags: JSON_THROW_ON_ERROR);
$facehash = new Facehash;
$output = [];

foreach ($cases as $case) {
    $builder = $facehash->name($case['name']);

    foreach (['size', 'variant', 'format', 'initial', 'blink', 'colors'] as $option) {
        if (array_key_exists($option, $case)) {
            $builder = $builder->{$option}($case[$option]);
        }
    }

    $output[] = $builder->toSvg();
}

echo json_encode($output, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
