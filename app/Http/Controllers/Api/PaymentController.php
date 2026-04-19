<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\FileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
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
            ]);

            if ($request->hasFile('files')) {
                $fileService->store($payment, $request->file('files'), 'payments');
            }

            DB::commit();

            return response()->json([
                'message' => 'Post saved successfully!',
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
