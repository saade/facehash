<?php

namespace Saade\Facehash\Http\Controllers;

use Saade\Facehash\Facehash;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class FacehashController
{
    public function __invoke(Request $request): Response
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'size' => ['sometimes', 'integer', 'min:16', 'max:1024'],
            'variant' => ['sometimes', 'string', 'in:gradient,solid'],
            'initial' => ['sometimes'],
            'blink' => ['sometimes'],
            'colors' => ['sometimes', 'array'],
            'colors.*' => ['string', 'regex:/^#[0-9a-fA-F]{3,8}$/'],
        ]);

        $facehash = app(Facehash::class)->name($request->input('name'));

        if ($request->has('size')) {
            $facehash = $facehash->size((int) $request->input('size'));
        }

        if ($request->has('variant')) {
            $facehash = $facehash->variant($request->input('variant'));
        }

        if ($request->has('initial')) {
            $facehash = $facehash->initial(filter_var($request->input('initial'), FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->has('blink')) {
            $facehash = $facehash->blink(filter_var($request->input('blink'), FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->has('colors')) {
            $facehash = $facehash->colors($request->input('colors'));
        }

        $svg = $facehash->toSvg();
        $cacheControl = config('facehash.route.cache_control', 'public, max-age=31536000, immutable');

        return new Response($svg, 200, [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => $cacheControl,
        ]);
    }
}
