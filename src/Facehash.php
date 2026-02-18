<?php

namespace Saade\Facehash;

use Saade\Facehash\Data\FacehashData;
use Saade\Facehash\Enums\FaceType;
use Saade\Facehash\Enums\Format;
use Saade\Facehash\Enums\Variant;
use Saade\Facehash\Support\SvgRenderer;

class Facehash
{
    protected ?string $name = null;

    protected Variant $variant;

    protected Format $format;

    protected bool $enableBlink;

    protected bool $showInitial;

    protected int $size;

    /** @var list<string> */
    protected array $colorPalette;

    public function __construct()
    {
        $config = function_exists('config') ? config('facehash', []) : [];
        $defaults = $config['defaults'] ?? [];

        $this->variant = Variant::tryFrom($defaults['variant'] ?? 'gradient') ?? Variant::Gradient;
        $this->format = Format::tryFrom($defaults['format'] ?? 'circle') ?? Format::Circle;
        $this->enableBlink = $defaults['blink'] ?? false;
        $this->showInitial = $defaults['initial'] ?? true;
        $this->size = $defaults['size'] ?? 40;
        $this->colorPalette = $config['colors'] ?? ['#ec4899', '#f59e0b', '#3b82f6', '#f97316', '#10b981'];
    }

    public function name(string $name): static
    {
        $instance = clone $this;
        $instance->name = $name;

        return $instance;
    }

    public function variant(string|Variant $variant): static
    {
        $instance = clone $this;
        $instance->variant = $variant instanceof Variant ? $variant : (Variant::tryFrom($variant) ?? Variant::Gradient);

        return $instance;
    }

    public function format(string|Format $format): static
    {
        $instance = clone $this;
        $instance->format = $format instanceof Format ? $format : (Format::tryFrom($format) ?? Format::Circle);

        return $instance;
    }

    public function blink(bool $enable = true): static
    {
        $instance = clone $this;
        $instance->enableBlink = $enable;

        return $instance;
    }

    public function initial(bool $show = true): static
    {
        $instance = clone $this;
        $instance->showInitial = $show;

        return $instance;
    }

    public function size(int $size): static
    {
        $instance = clone $this;
        $instance->size = $size;

        return $instance;
    }

    /** @param list<string> $colors */
    public function colors(array $colors): static
    {
        $instance = clone $this;
        $instance->colorPalette = $colors;

        return $instance;
    }

    public function toSvg(): string
    {
        $data = $this->compute();
        $color = $this->getColor($data->colorIndex);

        return (new SvgRenderer)->render(
            data: $data,
            backgroundColor: $color,
            size: $this->size,
            variant: $this->variant,
            format: $this->format,
            showInitial: $this->showInitial,
            enableBlink: $this->enableBlink,
        );
    }

    public function toBase64(): string
    {
        return base64_encode($this->toSvg());
    }

    public function toUri(): string
    {
        return 'data:image/svg+xml;base64,' . $this->toBase64();
    }

    protected function compute(): FacehashData
    {
        if ($this->name === null || $this->name === '') {
            throw new \InvalidArgumentException('Name is required. Call ->name() before generating.');
        }

        $hash = self::stringHash($this->name);

        $faceTypes = FaceType::cases();
        $faceIndex = $hash % count($faceTypes);
        $colorIndex = $hash % count($this->colorPalette);
        $positionIndex = $hash % count(self::SPHERE_POSITIONS);
        $position = self::SPHERE_POSITIONS[$positionIndex];

        return new FacehashData(
            faceType: $faceTypes[$faceIndex],
            colorIndex: $colorIndex,
            rotation: $position,
            initial: mb_strtoupper(mb_substr($this->name, 0, 1)),
        );
    }

    protected function getColor(int $index): string
    {
        $palette = count($this->colorPalette) > 0 ? $this->colorPalette : ['#ec4899'];

        return $palette[$index % count($palette)];
    }

    /**
     * Port of stringHash() from src/utils/hash.ts.
     * Uses & 0xFFFFFFFF to simulate JS 32-bit integer behavior.
     */
    protected static function stringHash(string $str): int
    {
        $hash = 0;

        for ($i = 0, $len = strlen($str); $i < $len; $i++) {
            $char = ord($str[$i]);
            $hash = (($hash << 5) - $hash + $char) & 0xFFFFFFFF;
        }

        // Convert unsigned 32-bit to signed (matching JS behavior) then abs
        if ($hash >= 0x80000000) {
            $hash -= 0x100000000;
        }

        return abs($hash);
    }

    /**
     * 9 sphere positions for 3D effect, ported from facehash-data.ts.
     */
    protected const SPHERE_POSITIONS = [
        ['x' => -1, 'y' => 1],  // down-right
        ['x' => 1, 'y' => 1],   // up-right
        ['x' => 1, 'y' => 0],   // up
        ['x' => 0, 'y' => 1],   // right
        ['x' => -1, 'y' => 0],  // down
        ['x' => 0, 'y' => 0],   // center
        ['x' => 0, 'y' => -1],  // left
        ['x' => -1, 'y' => -1], // down-left
        ['x' => 1, 'y' => -1],  // up-left
    ];
}
