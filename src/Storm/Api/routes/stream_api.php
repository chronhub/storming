<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Storm\Chronicler\Api\Controllers\Stream\CreateStreamApi;
use Storm\Chronicler\Api\Controllers\Stream\DeleteStreamApi;
use Storm\Chronicler\Api\Controllers\Stream\RequestStreamExistsApi;
use Storm\Chronicler\Api\Controllers\Stream\RetrieveStreamFromIncludedPositionApi;
use Storm\Chronicler\Api\Controllers\Stream\RetrieveStreamFromToPositionApi;

Route::group(['prefix' => 'stream'], function () {
    Route::get('/', RequestStreamExistsApi::class);
    Route::get('/from_to', RetrieveStreamFromToPositionApi::class);
    Route::get('/from', RetrieveStreamFromIncludedPositionApi::class);
    Route::post('/', CreateStreamApi::class);
    Route::delete('/', DeleteStreamApi::class);
});
