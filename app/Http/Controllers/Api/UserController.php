<?php

namespace App\Http\Controllers\Api;

use App\Events\RemoteControlEvent;
use App\Events\UserEvent;
use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::where('role', '!=', 'remote')->get();

        return response()->json($users);
    }

    public function addPlan(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'plan_id' => 'nullable|exists:plans,id',
            'custom' => 'nullable|numeric',
        ]);

        $user = User::with('karaokes')->findOrFail($validated['user_id']);

        // Determine how many days to add
        if (!empty($validated['plan_id'])) {
            $plan = Plan::findOrFail($validated['plan_id']);
            $daysToAdd = $plan->days;
        } elseif (!empty($validated['custom'])) {
            $daysToAdd = $validated['custom'];
        } else {
            return response()->json([
                'message' => 'Either plan_id or custom days is required'
            ], 422);
        }

        // Handle null expires_at safely
        $currentExpiry = $user->expires_at
            ? ( $user->subscription_status ? Carbon::parse($user->expires_at) : now() )
            : now();

        $user->update([
            'expires_at' => $currentExpiry->addDays((float) $daysToAdd),
        ]);

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
            'message' => 'Added successfully',
            'expires_at' => $user->expires_at,
        ]);
    }

    public function addUnlimited(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $user = User::with('karaokes')->findOrFail($validated['user_id']);

        $user->update([
            'expires_at' => null,
        ]);

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
            'message' => 'Added successfully',
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'contact_number' => ['required', 'string', 'max:11', 'min:11'],
            'area' => ['required', 'string', 'max:255'],
            'notes' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'role' => ['required', 'string'],
        ]);

        $user = User::create([
            'name' => $request->name,
            'contact_number' => $request->contact_number,
            'area' => $request->area,
            'notes' => $request->notes,
            'password' => Hash::make(1234),
            'email' => $request->email,
            'email_verified_at' => Carbon::now(),
            'role' => $request->role,
        ]);

        return response()->noContent();
    }

    public function show($id)
    {
        return User::with("activityLogs")->findOrFail($id);
    }

    public function updateRole(Request $request){
        $validated = $request->validate([
            'ids' => 'required|array',
            'role' => 'required|string',
        ]);

        DB::table('users')
            ->whereIn('id', $validated['ids'])
            ->update(['role' => $validated['role']]);

        $users = User::whereIn('id', $validated['ids'])->get();

        // foreach($users as $user){            
        //     ActivityLogger::log('update', 'account', "Updated account: #" . $user->id . " " . $user->name . " (changed role to " . $request->role . ")");
        // }

        return response()->json(['message' => 'Roles updated successfully']);
    }

    public function updateStatus(Request $request){
        $validated = $request->validate([
            'ids' => 'required|array',
            'status' => 'required|string',
        ]);

        DB::table('users')
            ->whereIn('id', $validated['ids'])
            ->update(['status' => $validated['status']]);

        $users = User::whereIn('id', $validated['ids'])->get();

        // foreach($users as $user){            
        //     ActivityLogger::log('update', 'account', "Updated account: #" . $user->id . " " . $user->name . " (changed role to " . $request->role . ")");
        // }

        return response()->json(['message' => 'Status updated successfully']);
    }

    public function changePassword(Request $request, $id){
        $user = User::where("id", $id)->first();

        if($user){
            $validator = Validator::make($request->all(), [
                "password" => "required|confirmed|string|min:6"
            ]);

            if($validator->fails()){
                return response()->json([
                    "status" => "422",
                    "message" => $validator->errors()
                ], 422);
            } else {
                $user->update([
                    "password" => Hash::make($request->password)
                ]);

                if($user){                    
                    return response()->json([
                        "status" => "200",
                        "message" => "Password Updated Successfully"
                    ], 200);
                } else {
                    return response()->json([
                        "status" => "500",
                        "message" => "Something Went Wrong"
                    ]);
                }
            }
        } else {
            return response()->json([
                "status" => "404",
                "message" => "User Not Found"
            ], 404);
        }
    }

    public function update(Request $request, $id)
    {
        $user = User::where("id", $id)->first();

        if ($user) {
            $validator = Validator::make($request->all(), [
                'username' => 'required|string|min:3|max:50|unique:users,name,' . $user->id ,
                'name' => 'required|string',
                'role' => 'required|string',
                'email' => 'required|email|unique:users,email,' . $user->id,
            ]);

            if ($validator->fails()) {
                return response()->json([
                    "message" => $validator->errors()
                ], 422);
            } else {
                $user->update([
                    "username" => $request->username,
                    "name" => $request->name,
                    "role" => $request->role,
                    "email" => $request->email,
                ]);

                return response()->json([
                    "status" => "200",
                    "message" => "Account Updated Successfully",
                    // "user" => $user,
                ], 200);
            }
        } else {
            return response()->json([
                "status" => "404",
                "message" => "User not found"
            ], 404);
        }
    }

    public function resetPasswordDefault(Request $request){
        $request->validate([
            'id' => 'required',
        ]);

        $user = User::findOrFail($request->id);

        $user->update([
            "password" => Hash::make(1234),
        ]);

        // ActivityLogger::log('reset', 'auth', "Reset the password for account: #" . $user->id . " " . $user->name);

        return response()->json(["message" => "Password Reset Success"], 200);
    }

    public function delete(Request $request){
        $validated = $request->validate([
            'ids' => 'required|array',
        ]);

        $users = User::whereIn('id', $validated['ids'])->get();

        User::whereIn('id', $validated['ids'])->delete();

        // foreach($users as $user){
        //     ActivityLogger::log('delete', 'account', "Deleted account: #" . $user->id . " " . $user->name);
        // }

        return response()->json(['message' => 'Users deleted successfully']);
    }

}
