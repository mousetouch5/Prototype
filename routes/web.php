<?php

use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response('OK', 200);
});

// Serve React app for all routes
Route::get('/{any}', function () {
    return view('react');
})->where('any', '.*');
