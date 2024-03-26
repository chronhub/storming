<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Storm\Chronicler\Http\Controllers\EventStream\CreateEventStreamApi;
use Storm\Chronicler\Http\Controllers\EventStream\RequestEventStreamExistsApi;

Route::group(['prefix' => 'event-stream'], function () {
    Route::get('/', RequestEventStreamExistsApi::class);

    Route::post('/', CreateEventStreamApi::class);
});
