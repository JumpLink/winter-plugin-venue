<?php

use Illuminate\Support\Facades\Route;
use JumpLink\Venue\Classes\VenueFeed;

// The knowledge feed. Public information only, read by sprechstunde and similar services.
Route::get('api/jumplink/venue/feed', function () {
    return response()->json(VenueFeed::current(), 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        ->header('Cache-Control', 'public, max-age=300');
})->middleware('throttle:60,1');
