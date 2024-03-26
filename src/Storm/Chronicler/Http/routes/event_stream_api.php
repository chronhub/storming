<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Storm\Chronicler\Http\Controllers\EventStream\CreateEventStreamApi;
use Storm\Chronicler\Http\Controllers\EventStream\RequestEventStreamExists;

Route::group(['prefix' => 'event-stream'], function () {
    Route::get('/', RequestEventStreamExists::class);

    Route::post('/', CreateEventStreamApi::class);
});
