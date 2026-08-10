/**
 * Facehash — browser port of the PHP renderer.
 *
 * This is a line-for-line port of `src/Facehash.php` + `src/Support/SvgRenderer.php`
 * so the docs playground can render arbitrary names without a PHP backend.
 *
 * It carries no face data of its own: `docs/build.php` exports the paths, sphere
 * positions, palette and defaults straight out of the PHP classes into
 * `facehash-data.js`, so the geometry can never drift from the package.
 *
 * `docs/parity.mjs` renders a matrix of inputs through both implementations and
 * diffs them, guarding the logic that *is* duplicated here.
 */

const encoder = new TextEncoder();

/**
 * Format a float the way PHP's string interpolation does (precision=14,
 * trailing zeros trimmed), so the emitted markup matches the PHP renderer byte
 * for byte.
 */
function num(value) {
    if (Number.isInteger(value)) {
        return String(value);
    }

    return String(Number(value.toPrecision(14)));
}

/** PHP's round(): half away from zero, unlike Math.round()'s half up. */
function phpRound(value) {
    return value < 0 ? -Math.round(-value) : Math.round(value);
}

/** Mirror of Enum::tryFrom() ?? fallback in the PHP builder. */
function oneOf(value, allowed, fallback) {
    return allowed.includes(value) ? value : fallback;
}

/** htmlspecialchars($s, ENT_XML1) — no quote flag, so only &, < and > are escaped. */
function escapeXml(value) {
    return value.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

/**
 * Port of stringHash() from src/Facehash.php.
 *
 * PHP hashes raw bytes (strlen/ord), so we hash the UTF-8 encoding rather than
 * UTF-16 code units — that keeps non-ASCII names in sync with the package.
 */
function stringHash(str) {
    let hash = 0;

    for (const byte of encoder.encode(str)) {
        hash = ((hash << 5) - hash + byte) | 0;
    }

    return Math.abs(hash);
}

/**
 * The PHP renderer derives clip/gradient ids from md5(). The exact digest is
 * cosmetic — ids only need to be stable and collision-free within a document —
 * so we use a cheap FNV-1a instead of shipping an md5 implementation.
 */
function elementId(prefix, seed) {
    let hash = 0x811c9dc5;

    for (const byte of encoder.encode(seed)) {
        hash = Math.imul(hash ^ byte, 0x01000193) >>> 0;
    }

    return `${prefix}-${hash.toString(16).padStart(8, '0')}`;
}

export function createFacehash(data) {
    const { faces, faceTypes, spherePositions, colors: defaultColors, defaults } = data;

    function compute(name, palette) {
        if (!name) {
            throw new Error('Name is required.');
        }

        const hash = stringHash(name);

        return {
            faceType: faceTypes[hash % faceTypes.length],
            color: palette[hash % palette.length],
            rotation: spherePositions[hash % spherePositions.length],
            initial: (Array.from(name)[0] ?? '').toUpperCase(),
        };
    }

    function toSvg(options = {}) {
        const {
            name,
            size = defaults.size,
            initial: showInitial = defaults.initial,
            blink: enableBlink = defaults.blink,
        } = options;

        const variant = oneOf(options.variant ?? defaults.variant, ['gradient', 'solid'], 'gradient');
        const format = oneOf(options.format ?? defaults.format, ['circle', 'square', 'squircle'], 'circle');
        const palette = options.colors?.length ? options.colors : defaultColors;
        const face = compute(name, palette);
        const { viewBox, paths } = faces[face.faceType];

        // Parse viewBox dimensions
        const [, , vbWidth, vbHeight] = viewBox.split(' ').map(Number);
        const aspectRatio = vbWidth / vbHeight;

        // Face takes up ~60% of the container
        const faceWidth = size * 0.6;
        const faceHeight = faceWidth / aspectRatio;

        // Font size for initial (26% of size)
        const fontSize = size * 0.26;

        // 3D effect offset
        const offsetMagnitude = size * 0.05;
        const offsetX = face.rotation.y * offsetMagnitude;
        const offsetY = -face.rotation.x * offsetMagnitude;

        // Center position for face group
        const faceCenterX = size / 2 + offsetX;
        const faceCenterY = size / 2 + offsetY;

        // Face SVG position (centered), shifted up to make room for the initial
        const faceX = faceCenterX - faceWidth / 2;
        let faceY = faceCenterY - faceHeight / 2;

        if (showInitial) {
            const totalHeight = faceHeight + size * 0.08 + fontSize;
            faceY = faceCenterY - totalHeight / 2;
        }

        const seed = `${face.initial}${size}${format}`;
        const clipId = elementId('clip', seed);
        const gradId = elementId('grad', seed);

        const half = size / 2;
        const radius = phpRound(size * 0.22);

        const shape = (fill) => {
            const attrs = fill ? ` fill="${fill}"` : '';

            switch (format) {
                case 'circle':
                    return `<circle cx="${num(half)}" cy="${num(half)}" r="${num(half)}"${attrs}/>`;
                case 'square':
                    return `<rect width="${num(size)}" height="${num(size)}"${attrs}/>`;
                case 'squircle':
                    return `<rect width="${num(size)}" height="${num(size)}" rx="${num(radius)}"${attrs}/>`;
            }
        };

        let svg = `<svg xmlns="http://www.w3.org/2000/svg" width="${num(size)}" height="${num(size)}" viewBox="0 0 ${num(size)} ${num(size)}" fill="none">`;

        // Defs: clipPath + optional gradient
        svg += '<defs>';
        svg += `<clipPath id="${clipId}">${shape(null)}</clipPath>`;

        if (variant === 'gradient') {
            svg += `<radialGradient id="${gradId}" cx="50%" cy="50%" r="50%" fx="50%" fy="50%">`;
            svg += '<stop offset="0%" stop-color="white" stop-opacity="0.15"/>';
            svg += '<stop offset="60%" stop-color="white" stop-opacity="0"/>';
            svg += '</radialGradient>';
        }

        svg += '</defs>';

        // Clipped group
        svg += `<g clip-path="url(#${clipId})">`;

        // Background
        svg += shape(face.color);

        // Gradient overlay
        if (variant === 'gradient') {
            svg += shape(`url(#${gradId})`);
        }

        // Blink animation style
        if (enableBlink) {
            svg += '<style>';
            svg += '@keyframes facehash-blink-left{0%,92%,100%{transform:scaleY(1)}96%{transform:scaleY(0.05)}}';
            svg += '@keyframes facehash-blink-right{0%,88%,100%{transform:scaleY(1)}92%{transform:scaleY(0.05)}}';
            svg += '.fh-eye-left{animation:facehash-blink-left 4s ease-in-out 0.5s infinite;transform-origin:center;transform-box:fill-box}';
            svg += '.fh-eye-right{animation:facehash-blink-right 3.5s ease-in-out 0s infinite;transform-origin:center;transform-box:fill-box}';
            svg += '</style>';
        }

        // Face eyes SVG
        svg += `<svg x="${num(faceX)}" y="${num(faceY)}" width="${num(faceWidth)}" height="${num(faceHeight)}" viewBox="${viewBox}" fill="none" xmlns="http://www.w3.org/2000/svg">`;

        paths.forEach((d, i) => {
            const className = enableBlink ? ` class="${i === 0 ? 'fh-eye-right' : 'fh-eye-left'}"` : '';
            svg += `<path d="${d}" fill="black"${className}/>`;
        });

        svg += '</svg>';

        // Initial letter
        if (showInitial) {
            const textY = faceY + faceHeight + size * 0.08 + fontSize * 0.85;

            svg += `<text x="${num(faceCenterX)}" y="${num(textY)}" text-anchor="middle" font-family="monospace" font-weight="700" font-size="${num(fontSize)}" fill="black">`;
            svg += escapeXml(face.initial);
            svg += '</text>';
        }

        // Close clipped group
        svg += '</g>';
        svg += '</svg>';

        return svg;
    }

    const toBase64 = (options) => {
        let binary = '';

        for (const byte of encoder.encode(toSvg(options))) {
            binary += String.fromCharCode(byte);
        }

        return btoa(binary);
    };

    return {
        toSvg,
        toBase64,
        toUri: (options) => `data:image/svg+xml;base64,${toBase64(options)}`,
    };
}
