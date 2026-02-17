<?php

namespace Saade\Facehash\Support;

use Saade\Facehash\Data\FacehashData;
use Saade\Facehash\Data\FaceSvgData;
use Saade\Facehash\Enums\Variant;

final class SvgRenderer
{
    public function render(
        FacehashData $data,
        string $backgroundColor,
        int $size,
        Variant $variant,
        bool $showInitial,
        bool $enableBlink,
    ): string {
        $svgData = FaceSvgData::get($data->faceType);
        $viewBox = $svgData['viewBox'];
        $paths = $svgData['paths'];

        // Parse viewBox dimensions
        $vbParts = explode(' ', $viewBox);
        $vbWidth = (float) $vbParts[2];
        $vbHeight = (float) $vbParts[3];
        $aspectRatio = $vbWidth / $vbHeight;

        // Face takes up ~60% of the container
        $faceWidth = $size * 0.6;
        $faceHeight = $faceWidth / $aspectRatio;

        // Font size for initial (26% of size)
        $fontSize = $size * 0.26;

        // 3D effect offset
        $offsetMagnitude = $size * 0.05;
        $offsetX = $data->rotation['y'] * $offsetMagnitude;
        $offsetY = -$data->rotation['x'] * $offsetMagnitude;

        // Center position for face group
        $faceCenterX = ($size / 2) + $offsetX;
        $faceCenterY = ($size / 2) + $offsetY;

        // Face SVG position (centered)
        $faceX = $faceCenterX - ($faceWidth / 2);
        $faceY = $faceCenterY - ($faceHeight / 2);

        // Adjust face Y up if showing initial to make room
        if ($showInitial) {
            $initialHeight = $fontSize;
            $gap = $size * 0.08;
            $totalHeight = $faceHeight + $gap + $initialHeight;
            $faceY = $faceCenterY - ($totalHeight / 2);
        }

        $clipId = 'clip-' . substr(md5($data->initial . $size), 0, 8);
        $gradId = 'grad-' . substr(md5($data->initial . $size), 0, 8);

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 ' . $size . ' ' . $size . '" fill="none">';

        // Defs: clipPath + optional gradient
        $svg .= '<defs>';
        $svg .= '<clipPath id="' . $clipId . '"><circle cx="' . ($size / 2) . '" cy="' . ($size / 2) . '" r="' . ($size / 2) . '"/></clipPath>';

        if ($variant === Variant::Gradient) {
            $svg .= '<radialGradient id="' . $gradId . '" cx="50%" cy="50%" r="50%" fx="50%" fy="50%">';
            $svg .= '<stop offset="0%" stop-color="white" stop-opacity="0.15"/>';
            $svg .= '<stop offset="60%" stop-color="white" stop-opacity="0"/>';
            $svg .= '</radialGradient>';
        }

        $svg .= '</defs>';

        // Clipped group
        $svg .= '<g clip-path="url(#' . $clipId . ')">';

        // Background circle
        $svg .= '<circle cx="' . ($size / 2) . '" cy="' . ($size / 2) . '" r="' . ($size / 2) . '" fill="' . $backgroundColor . '"/>';

        // Gradient overlay
        if ($variant === Variant::Gradient) {
            $svg .= '<circle cx="' . ($size / 2) . '" cy="' . ($size / 2) . '" r="' . ($size / 2) . '" fill="url(#' . $gradId . ')"/>';
        }

        // Blink animation style
        if ($enableBlink) {
            $svg .= '<style>';
            $svg .= '@keyframes facehash-blink-left{0%,92%,100%{transform:scaleY(1)}96%{transform:scaleY(0.05)}}';
            $svg .= '@keyframes facehash-blink-right{0%,88%,100%{transform:scaleY(1)}92%{transform:scaleY(0.05)}}';
            $svg .= '.fh-eye-left{animation:facehash-blink-left 4s ease-in-out 0.5s infinite;transform-origin:center;transform-box:fill-box}';
            $svg .= '.fh-eye-right{animation:facehash-blink-right 3.5s ease-in-out 0s infinite;transform-origin:center;transform-box:fill-box}';
            $svg .= '</style>';
        }

        // Face eyes SVG
        $svg .= '<svg x="' . $faceX . '" y="' . $faceY . '" width="' . $faceWidth . '" height="' . $faceHeight . '" viewBox="' . $viewBox . '" fill="none" xmlns="http://www.w3.org/2000/svg">';

        foreach ($paths as $i => $d) {
            $class = '';
            if ($enableBlink) {
                $class = ' class="' . ($i === 0 ? 'fh-eye-right' : 'fh-eye-left') . '"';
            }
            $svg .= '<path d="' . $d . '" fill="black"' . $class . '/>';
        }

        $svg .= '</svg>';

        // Initial letter
        if ($showInitial) {
            $textX = $faceCenterX;
            $textY = $faceY + $faceHeight + ($size * 0.08) + ($fontSize * 0.85);

            $svg .= '<text x="' . $textX . '" y="' . $textY . '" text-anchor="middle" font-family="monospace" font-weight="700" font-size="' . $fontSize . '" fill="black">';
            $svg .= htmlspecialchars($data->initial, ENT_XML1);
            $svg .= '</text>';
        }

        // Close clipped group
        $svg .= '</g>';
        $svg .= '</svg>';

        return $svg;
    }
}
