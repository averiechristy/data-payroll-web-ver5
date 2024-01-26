<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DashboardNewController;
use App\Http\Controllers\DataLeadsController;
use App\Http\Controllers\KCUController;
use App\Http\Controllers\RekapAkuisisiController;
use App\Http\Controllers\RekapCallController;
use App\Http\Controllers\UserController;
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

Route::get('/', function () {
    return view('auth.login');
});

Route::middleware('auth')->group(function () {
Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

Route::get('dashboardnew', [DashboardNewController::class, 'index'])->name('dashboardnew');



Route::get('user/index', [UserController::class, 'index'])->name('user.index');
Route::get('user/create', [UserController::class,'create'])->name('user.create');
Route::post('user/simpan',[UserController::class,'store'])->name('user.simpan');
Route::get('/tampiluser/{id}',[UserController::class,'show'])->name('tampiluser');
Route::post('/updateuser/{id}',[UserController::class,'update'])->name('updateuser');
Route::delete('/deleteuser/{id}', [UserController::class, 'destroy'])->name('deleteuser');
Route::post('/user/{user}/reset-password', [UserController::class,'resetPassword'])->name('reset-password');


Route::get('kcu/index',[KCUController::class,'index'])->name('kcu.index');
Route::get('kcu/create',[KCUController::class,'create'])->name('kcu.create');
Route::post('kcu/simpan',[KCUController::class,'store'])->name('kcu.simpan');
Route::get('/tampilkcu/{id}',[KCUController::class,'show'])->name('tampilkcu');
Route::post('/updatekcu/{id}',[KCUController::class,'update'])->name('updatekcu');
Route::delete('/deletekcu/{id}', [KCUController::class, 'destroy'])->name('deletekcu');


Route::get('dataleads/index', [DataLeadsController::class, 'index'])->name('dataleads.index');
Route::post('dataleads/import',[DataLeadsController::class,'import'])->name('dataleads.import');

Route::get('rekapcall/index',[RekapCallController::class,'index'])->name('rekapcall.index');
Route::post('rekapcall/import',[RekapCallController::class,'import'])->name('rekapcall.import');
// web.php
Route::get('/dataleads/export', [DataLeadsController::class,'export'])->name('dataleads.export');


Route::post('rekapakuisisi/import',[RekapAkuisisiController::class,'import'])->name('rekapakuisisi.import');

Route::post('rekapakuisisi/importbulan',[RekapAkuisisiController::class,'importbulan'])->name('rekapakuisisi.importbulan');


Route::post('/filter-data', [DashboardController::class,'filterdata'])->name('filter-data');
Route::get('/reset-filter',[DashboardController::class,'reset'])->name('reset');


Route::post('/delete-data', [DataLeadsController::class,'deleteData'])->name('delete.data');

Route::post('/filter-data-dataleads', [DataLeadsController::class, 'filterData'])->name('filter.data');
Route::get('admin/changepassword', [AuthController::class,'showChangePasswordForm'])->name('password');
Route::post('admin/changepassword', [AuthController::class,'adminchangePassword'])->name('change-password');
Route::post('/rename/{id}', [DataLeadsController::class,'rename'])->name('rename');

Route::get('/tampilnama/{id}',[DataLeadsController::class,'show'])->name('tampilnama');


Route::post('/updatenama/{id}',[DataLeadsController::class,'update'])->name('updatenama');


});

Route::get('/login', [AuthController::class,'index'])->name('login');
Route::post('/login', [AuthController::class,'login']);
Route::post('/logout', [AuthController::class,'logout'])->name('logout');