<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

// Proyecto API-only: la raíz solo informa. Los endpoints viven en routes/api.php.
Route::get('/', fn () => response()->json([
    'name' => config('app.name'),
    'message' => 'Pokémon Challenge API. Ver documentación en docs/api.http.',
]));
