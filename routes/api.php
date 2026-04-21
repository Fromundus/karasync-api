<?php

use App\Exports\PatientsExport;
use App\Exports\RegisteredMembersExport;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\KaraokeController;
use App\Http\Controllers\Api\MemberController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PlanController;
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
        Route::get('/', [KaraokeController::class, 'index']);
        Route::get('/scan/{karaokeId}', [KaraokeController::class, 'scan']);
        Route::post('/register', [KaraokeController::class, 'register']);
        Route::put('/{karaokeId}', [KaraokeController::class, 'update']);
        Route::delete('/{karaoke}', [KaraokeController::class, 'delete']);
    });

    Route::middleware(['subscribed'])->group(function(){
        Route::prefix('/remote')->group(function(){
            Route::post('/', [RemoteController::class, 'remote']); // for the button actions
            Route::get('/search', [RemoteController::class, 'search']);
            Route::get('/search/youtube', [RemoteController::class, 'youtubeSearch']);
            Route::post('/reserve', [RemoteController::class, 'reserve']);
            Route::put('/next', [RemoteController::class, 'next']);
            Route::put('/stop-all', [RemoteController::class, 'stopAll']);
        });
    });

    Route::prefix('/payments')->group(function(){
        Route::get('/', [PaymentController::class, 'index']);
        Route::post('/', [PaymentController::class, 'store']);
        Route::put('/status', [PaymentController::class, 'updateStatus']);
    });

    Route::prefix('/users')->group(function(){
        Route::put('/password', [UserController::class, 'updatePassword']);
    });

    Route::middleware(['admin'])->group(function(){
        Route::prefix('/users')->group(function(){
            Route::get('/', [UserController::class, 'index']);
            Route::post('/plan', [UserController::class, 'addPlan']);
            Route::post('/unlimited', [UserController::class, 'addUnlimited']);
        });

        // Route::prefix('/payments')->group(function(){
        // });
    });
});

Route::post('/login', [AuthController::class, 'login']);
Route::post('remote-login', [AuthController::class, 'remoteLogin']);
Route::post('/register', [AuthController::class, 'register']);

Route::prefix('/karaokes')->group(function(){
    Route::get('/save/{karaokeId}', [KaraokeController::class, 'store']);
    Route::get('/{karaokeId}', [KaraokeController::class, 'show']);
    Route::post('/heartbeat', [KaraokeController::class, 'heartbeat']);
    Route::get('/{karaokeId}/{connectionToken}', [KaraokeController::class, 'connectRemote']);
});

Route::prefix('/remote')->group(function(){
    Route::put('/next', [RemoteController::class, 'next']);
});

Route::prefix('/plans')->group(function(){
    Route::get('/', [PlanController::class, 'index']);
    Route::put('/update', [PlanController::class, 'bulkUpdate']);
});

Route::get('/test', function(){
    return response()->json([
        "message" => "success"
    ], 200);
});

//FILES
Route::get('/files/{filename}', function ($filename) {
    $path = storage_path('app/public/' . $filename);

    if (!\File::exists($path)) {
        abort(404);
    }

    return response()->file($path);
})->where('filename', '.*');