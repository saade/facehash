<?php

use Saade\Facehash\Facades\Facehash;
use Saade\Facehash\Tests\TestCase;

pest()->extend(TestCase::class)->in(__DIR__);

/**
 * How far the face sits from the centre of the avatar, as [right, down] steps.
 *
 * @return array{int, int}
 */
function gaze(string $name): array
{
    $svg = Facehash::name($name)->size(100)->initial(false)->toSvg();

    preg_match('/<svg x="([^"]+)" y="([^"]+)" width="([^"]+)" height="([^"]+)"/', $svg, $face);

    return [
        (int) round(($face[1] + $face[3] / 2 - 50) / 5),
        (int) round(($face[2] + $face[4] / 2 - 50) / 5),
    ];
}
