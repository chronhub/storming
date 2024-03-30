<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Storm\Chronicler\Http\Controllers\CreateStreamApi;
use Storm\Chronicler\Http\Controllers\DeleteStreamApi;
use Storm\Chronicler\Http\Controllers\RequestStreamExistsApi;
use Storm\Chronicler\Http\Controllers\RetrieveFromToStreamPositionApi;

Route::group(['prefix' => 'stream'], function () {
    Route::get('/', RequestStreamExistsApi::class);

    Route::get('/from_to', RetrieveFromToStreamPositionApi::class);

    Route::post('/', CreateStreamApi::class);

    Route::delete('/', DeleteStreamApi::class);
});
