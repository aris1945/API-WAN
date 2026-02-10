<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\OdcController;
use App\Http\Controllers\Api\OdpController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SiteController;
use App\Http\Controllers\Api\SpbuController;
use App\Http\Controllers\Api\AbsensiController;
use App\Http\Controllers\Api\TicketController;
use App\Http\Controllers\Api\UserController;

// === ROUTE PUBLIC (Bisa diakses siapa saja) ===
Route::post('/register', [AuthController::class, 'register']); // Buat user baru
Route::post('/login', [AuthController::class, 'login']);       // Login
Route::post('/spbu/import', [SpbuController::class, 'import']);
Route::post('/sites/import', [SiteController::class, 'import']);


// === ROUTE PRIVATE (Wajib Login / Punya Token) ===
Route::middleware('auth:sanctum')->group(function () {
	
	Route::post('/tickets/{id}/log', [TicketController::class, 'addLog']); // Endpoint khusus log
	
	Route::get('/tickets/next-number', [TicketController::class, 'getNextNumber']);
	Route::apiResource('/tickets', TicketController::class);
    
    // Fitur Logout
    Route::post('/logout', function (Request $request) {
        // Hapus token yang sedang dipakai
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out successfully']);
    });
    
    // Fitur User Profile (Cek siapa yang login)
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::get('/odc-search', [OdcController::class, 'search']);
    Route::get('/odp-nearest', [OdpController::class, 'nearest']);
    Route::get('/absensi', [AbsensiController::class, 'getReport']);
    // Route SPBU
    Route::get('/spbu', [SpbuController::class, 'index']);
	Route::get('/users/teknisi', [UserController::class, 'getTeknisi']);
    

    // --- DATA SITES DIKUNCI DI SINI ---
    // Pindahkan semua route 'sites' Anda ke dalam grup ini
    Route::get('/sites', [SiteController::class, 'index']);
    Route::get('/sites/{site_id}', [SiteController::class, 'show']);
    Route::post('/sites', [SiteController::class, 'store']);

Route::post('/tickets', [TicketController::class, 'store']);
    Route::get('/tickets', [TicketController::class, 'index']);
	
    Route::put('/tickets/{id}', [TicketController::class, 'update']);
    
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
