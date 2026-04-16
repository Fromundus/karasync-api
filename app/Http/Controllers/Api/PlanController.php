<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\Request;

class PlanController extends Controller
{
    public function index(Request $request)
    {
        $plans = Plan::all();

        return response()->json($plans);
    }

    public function bulkUpdate(Request $request)
    {
        $plans = $request->input('plans');

        if (!is_array($plans)) {
            return response()->json([
                'message' => 'Invalid data format'
            ], 422);
        }

        foreach ($plans as $planData) {
            if (!isset($planData['id'])) {
                continue;
            }

            $plan = Plan::find($planData['id']);

            if (!$plan) {
                continue;
            }

            $plan->update([
                'name' => $planData['name'] ?? $plan->name,
                'description' => $planData['description'] ?? $plan->description,
                'price' => $planData['price'] ?? $plan->price,
                'days' => $planData['days'] ?? $plan->days,
                'recommended' => $planData['recommended'] ?? $plan->recommended,
                'bottom_description' => $planData['bottom_description'] ?? $plan->bottom_description,
            ]);
        }

        return response()->json([
            'message' => 'Plans updated successfully'
        ]);
    }
}
