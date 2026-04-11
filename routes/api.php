<?php

use App\Exports\PatientsExport;
use App\Exports\RegisteredMembersExport;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\KaraokeController;
use App\Http\Controllers\Api\MemberController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\Api\RegisteredMemberController;
use App\Http\Controllers\Api\RemoteController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\TicketController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\TicketExportController;
use App\Models\Patient;
use App\Models\PatientRecord;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Maatwebsite\Excel\Facades\Excel;

Route::middleware(['auth:sanctum'])->group(function(){
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::prefix('/karaokes')->group(function(){
        Route::get('/scan/{karaokeId}', [KaraokeController::class, 'scan']);
        Route::post('/register', [KaraokeController::class, 'register']);
        Route::put('/{karaokeId}', [KaraokeController::class, 'update']);
        Route::delete('/{karaoke}', [KaraokeController::class, 'delete']);
    });
});

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

Route::prefix('/karaokes')->group(function(){
    Route::post('/', [KaraokeController::class, 'store']);
    Route::get('/{karaokeId}', [KaraokeController::class, 'show']);
    Route::get('/{karaokeId}/{connectionToken}', [KaraokeController::class, 'connectRemote']);
});

Route::prefix('/remote')->group(function(){
    Route::get('/search', [RemoteController::class, 'search']);
    Route::post('/reserve', [RemoteController::class, 'reserve']);
});

Route::get('/test', function(){
    return response()->json([
        "message" => "success"
    ], 200);
});