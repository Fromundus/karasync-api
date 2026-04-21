<?php

namespace App\Http\Controllers\Api;

use App\Events\RemoteControlEvent;
use App\Events\UserEvent;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\User;
use App\Services\FileService;
use App\Services\ReferenceService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function index(Request $request){
        $query = Payment::with(['user', 'files'])->orderByDesc('created_at');

        $user = $request->user();

        if($user->role === "user"){
            $payments = $query->where('user_id', $user->id)->get();
        } else {
            $payments = $query->get();
        }

        return response()->json($payments);
    }

    public function store(Request $request, FileService $fileService)
    {
        $validated = $request->validate([
            'base_price' => 'required|numeric',
            'amount' => 'required|numeric|min:1',
            'days' => 'required|numeric|min:1',
            'files.*' => 'nullable|file|mimes:jpg,jpeg,png|max:102400',
        ]);

        try {
            DB::beginTransaction();

            $user = $request->user();

            $payment = Payment::create([
                'user_id' => $user->id,
                'base_price' => $validated["base_price"],
                'amount' => $validated["amount"],
                'days' => $validated["days"],
                'reference_number' => ReferenceService::generate(),
            ]);

            if ($request->hasFile('files')) {
                $fileService->store($payment, $request->file('files'), 'payments');
            }

            DB::commit();

            return response()->json([
                'message' => 'Payment saved successfully!',
            ]);
        } catch (\Throwable $e){
            DB::rollBack();
            return response()->json([
                'error'   => 'Failed to save payment',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateStatus(Request $request){
        $validated = $request->validate([
            "status" => "required|string",
            "payment_id" => "required",
            'custom' => 'nullable|numeric',
        ]);
        
        try {
            DB::beginTransaction();

            $payment = Payment::findOrFail($validated["payment_id"]);
            $paymentBefore = clone $payment;
            
            $user = User::with('karaokes')->findOrFail($payment->user_id);

            if($paymentBefore["status"] === "verified"){
                if($validated["status"] === "cancelled"){
                    $payment->update([
                        "status" => $validated["status"],
                    ]);

                    if (!empty($payment['custom_days'])) {
                        $daysToSub = $payment['custom_days'];
                    } else {
                        $daysToSub = $payment->days;
                    }

                    $currentExpiry = $user->expires_at
                        ? ( $user->subscription_status ? Carbon::parse($user->expires_at) : now() )
                        : now();

                    $user->update([
                        'expires_at' => $currentExpiry->subDays((float) $daysToSub),
                    ]);
                }
            } else {
                if($validated["status"] === "verified"){
                    $payment->update([
                        "status" => $validated["status"],
                        "custom_days" => $validated["custom"] ?? null,
                    ]);

                    if (!empty($validated['custom'])) {
                        $daysToAdd = $validated['custom'];
                    } else {
                        $daysToAdd = $payment->days;
                    }
    
                    // Handle null expires_at safely
                    $currentExpiry = $user->expires_at
                        ? ( $user->subscription_status ? Carbon::parse($user->expires_at) : now() )
                        : now();
    
                    $user->update([
                        'expires_at' => $currentExpiry->addDays((float) $daysToAdd),
                    ]);
                } else if($validated["status"] === "cancelled"){
                    $payment->update([
                        "status" => $validated["status"],
                    ]);
                }
            }

            DB::commit();

            $karaokes = $user->karaokes;

            foreach($karaokes as $karaoke){
                broadcast(new RemoteControlEvent(
                    $karaoke->karaoke_id,
                    "subscribe"
                ))->toOthers();
            }

            broadcast(new UserEvent(
                $user->id,
                "fetch"
            ))->toOthers();

            return response()->json([
                "message" => "Updated Successfully"
            ]);

        } catch (\Throwable $e){
            DB::rollBack();
            return response()->json([
                'error'   => 'Failed to save payment',
                'details' => $e->getMessage(),
            ], 500);
        }
        
        
    }
}
