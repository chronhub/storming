<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Storm\Chronicler\Http\Controllers\EventStream\CreateStreamApi;
use Storm\Chronicler\Http\Controllers\EventStream\DeleteStreamApi;
use Storm\Chronicler\Http\Controllers\EventStream\RequestStreamExistsApi;

Route::group(['prefix' => 'event-stream'], function () {
    Route::get('/', RequestStreamExistsApi::class);

    Route::post('/', CreateStreamApi::class);

    Route::delete('/', DeleteStreamApi::class);
});
