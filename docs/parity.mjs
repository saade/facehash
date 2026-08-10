/**
 * Guards the browser port against the PHP renderer.
 *
 * docs/assets/facehash.js duplicates the hash and SVG-composition logic from
 * src/ so the playground can run without a backend. This renders a matrix of
 * options through both and diffs the markup, so any change to one that isn't
 * mirrored in the other fails loudly.
 *
 *   node docs/parity.mjs
 */

import { execFileSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';
import { dirname, join } from 'node:path';

import { createFacehash } from './assets/facehash.js';

const here = dirname(fileURLToPath(import.meta.url));
const root = dirname(here);

const NAMES = [
    'Saade',
    'a',
    'alice@example.com',
    'Zoë',              // multi-byte — PHP hashes bytes, so the port must too
    '日本語',
    'A very long display name with spaces',
    '<script>',         // exercises XML escaping of the initial
    '1234567890',
];

const PALETTES = [undefined, ['#6366f1', '#8b5cf6', '#a78bfa'], ['#111111']];

/** Ids are derived from md5() in PHP and FNV-1a in JS — stable, but not equal. */
const normalize = (svg) => svg.replace(/(clip|grad)-[0-9a-f]+/g, '$1-x');

const cases = [];

for (const name of NAMES) {
    for (const size of [16, 24, 40, 63, 128, 501]) {
        for (const variant of ['gradient', 'solid']) {
            for (const format of ['circle', 'square', 'squircle']) {
                for (const initial of [true, false]) {
                    for (const blink of [true, false]) {
                        for (const colors of PALETTES) {
                            cases.push({ name, size, variant, format, initial, blink, ...(colors ? { colors } : {}) });
                        }
                    }
                }
            }
        }
    }
}

const expected = JSON.parse(
    execFileSync('php', [join(here, 'parity.php')], {
        cwd: root,
        input: JSON.stringify(cases),
        maxBuffer: 256 * 1024 * 1024,
        encoding: 'utf8',
    })
);

const dataModule = await import(join(root, '_site/assets/facehash-data.js')).catch(() => {
    throw new Error('Run `php docs/build.php` first — parity needs the exported face data.');
});

const facehash = createFacehash(dataModule.default);

let failures = 0;

cases.forEach((options, index) => {
    const actual = normalize(facehash.toSvg(options));
    const want = normalize(expected[index]);

    if (actual === want) {
        return;
    }

    failures++;

    if (failures <= 3) {
        console.error(`\n✗ ${JSON.stringify(options)}`);
        console.error(`  php: ${want}`);
        console.error(`  js:  ${actual}`);
    }
});

if (failures > 0) {
    console.error(`\n${failures} of ${cases.length} cases differ.`);
    process.exit(1);
}

console.log(`✓ ${cases.length} cases match the PHP renderer.`);
