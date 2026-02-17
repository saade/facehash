<?php

namespace Saade\Facehash\Data;

use Saade\Facehash\Enums\FaceType;

final readonly class FacehashData
{
    public function __construct(
        public FaceType $faceType,
        public int $colorIndex,
        public array $rotation,
        public string $initial,
    ) {}
}
