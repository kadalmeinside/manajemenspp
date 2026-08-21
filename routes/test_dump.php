<?php
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::post('/test-promo', function (Request $request) {
    return response()->json($request->all());
});
