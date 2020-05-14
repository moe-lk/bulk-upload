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
Route::get('downloadExcel', 'FileController@downloadTemplate');
Route::post('importExcel', 'ImportExport@import');
Route::post('exportExcel', 'ImportExport@export');
Route::post('updateQueueWithUnprocessedFiles/{id}/{action}', 'FileController@updateQueueWithUnprocessedFiles');

//Auth::routes();
Auth::routes(['register' => false]);

Route::get('/home', 'HomeController@index')->name('home');
Route::post('upload', 'FileController@upload')->name('upload');

Route::get('create', 'FilesController@create');
Route::get('index', 'FilesController@index');

<<<<<<< HEAD
Route::get('uploadcsv', 'ExaminationStudentsController@index');
Route::post('uploadFile', 'ExaminationStudentsController@uploadFile');
=======
>>>>>>> origin/UAT

Route::get('download/{filename}', 'FileController@downloadErrorFile')->where('filename', '[A-Za-z0-9\-\_\.]+');
Route::get('download_file/{filename}', 'FileController@downloadFile')->where('filename', '[A-Za-z0-9\-\_\.]+');


Route::get('/healthz', function () { return 'ok'; });
