<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Route::get('/', function () {
//     return view('welcome');
// });

Route::get('/', 'ImportExport@importExportView')->middleware('Role:HOMEROOM_TEACHER');
Route::get('/', 'ImportExport@importExportView')->middleware('Role:PRINCIPAL');
Route::get('downloadExcel', 'ImportExport@export');
Route::post('importExcel', 'ImportExport@import');

Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');
Route::post('upload', 'FileController@upload')->name('upload');
Route::get('excel/{filename}', function ($filename)
{
    $path = storage_path('sis-bulk-data-files/processed/' . $filename);

    if (!File::exists($path)) {
        abort(404);
    }

    $file = File::get($path);
    $type = File::mimeType($path);

    $response = Response::make($file, 200);
    $response->header("Content-Type", $type);

    return $response;
});
