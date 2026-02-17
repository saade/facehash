<?php

namespace Saade\Facehash\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Saade\Facehash\Facehash name(string $name)
 * @method static \Saade\Facehash\Facehash variant(string|\Saade\Facehash\Enums\Variant $variant)
 * @method static \Saade\Facehash\Facehash blink(bool $enable = true)
 * @method static \Saade\Facehash\Facehash initial(bool $show = true)
 * @method static \Saade\Facehash\Facehash size(int $size)
 * @method static \Saade\Facehash\Facehash colors(array $colors)
 * @method static string toSvg()
 * @method static string toBase64()
 * @method static string toUri()
 *
 * @see \Saade\Facehash\Facehash
 */
class Facehash extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Saade\Facehash\Facehash::class;
    }
}
