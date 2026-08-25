<?php

// use App\Http\Controllers\Api\ApiController;
// use App\Http\Controllers\Api\JekoPaymentController;
// use App\Http\Controllers\Api\ReceiptController;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('storage/documents/{file}', function ($file) {
    $path = base_path(env('UPLOADS_PATH') . $file);

    if (!file_exists($path)) {
        abort(404);
    }

    $fileContents = file_get_contents($path);
    $mimeType = mime_content_type($path);

    return Response::make($fileContents, 200, ['Content-Type' => $mimeType]);

})->where('file', '.*')->name('storage.documents');  // ← AJOUTER ICI

Route::get('/api/documentation', function () {
    return view('documentation.index',);
});

// Route::get('/', function () {
//     return view('welcome');
// });

