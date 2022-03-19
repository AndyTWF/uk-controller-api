<?php

use App\Models\Airfield\Airfield;
use App\Models\Stand\Stand;
use Illuminate\Support\Facades\Route;

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

Route::get('welcome', function () {
    return view('welcome');
})->name('welcome');

Route::get('stands', function () {
    return view('rent-a-stand', [
        'airfields' => Airfield::whereHas('stands')->orderBy('code')->get(),
        'stands' => Stand::notClosed()->get()->map(fn(Stand $stand) => [
            'stand_id' => $stand->id,
            'airfield_id' => $stand->airfield_id,
            'identifier' => $stand->identifier,
        ])->sortBy('identifier', SORT_NATURAL, false)->groupBy('airfield_id')
    ]);
})->name('rent-a-stand');

Route::get('dashboard', function () {
    return view('dashboard');
})->middleware(['auth'])->name('dashboard');

require __DIR__ . '/auth.php';
