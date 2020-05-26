<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api','core')->get('/user', function (Request $request) {
    return $request->user();
});
Route::middleware('auth:api','core')->get('/oauth/token','GrafanaOAuth@token');
Route::middleware('auth:api','core')->post('/oauth/auth', 'GrafanaOAuth@auth');

// Route::get('/oauth/token', 'GrafanaOAuth@token');
Auth::routes(['register' => false]);
