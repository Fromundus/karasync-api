<?php

namespace App\Http\Controllers\Api;

use App\Events\RemoteControlEvent;
use App\Http\Controllers\Controller;
use App\Models\Karaoke;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class KaraokeController extends Controller
{
    public function store(Request $request){
        $validated = $request->validate([
            "karaoke_id" => "required|string"
        ]);

        $karaoke = Karaoke::firstOrCreate([
            'karaoke_id' => $validated["karaoke_id"]
        ]);

        $karaoke = Karaoke::find($karaoke->id);

        return response()->json($karaoke);
    }

    public function register(Request $request){
        $validated = $request->validate([
            "karaoke_id" => "required|string",
            "name" => "required|string|max:100",
        ]);

        $karaoke = Karaoke::where('karaoke_id', $validated["karaoke_id"])->where('status', 'pending')->firstOrFail();

        $user = $request->user();

        try {
            DB::beginTransaction();
            
            $karaoke->update([
                'user_id' => $user->id,
                'name' => $validated["name"],
                'connection_token' => Str::random(64),
                'status' => 'active',
            ]);

            broadcast(new RemoteControlEvent(
                $karaoke->karaoke_id,
                "register"
            ))->toOthers();

            DB::commit();
        } catch (\Exception $e){
            DB::rollBack();

            throw $e;
        }

        return response()->json($karaoke);
    }

    public function update(Request $request, $karaokeId){
        $validated = $request->validate([
            "name" => "sometimes|string|max:100",
        ]);

        $karaoke = Karaoke::where('karaoke_id', $karaokeId)->firstOrFail();

        try {
            DB::beginTransaction();
            
            $karaoke->update([
                'name' => $validated["name"] ?? $karaoke->name,
            ]);

            DB::commit();

            return response()->json([
                "message" => "Karaoke updated successfully"
            ]);
        } catch (\Exception $e){
            DB::rollBack();

            throw $e;
        }
    }

    public function scan($karaokeId){
        $karaoke = Karaoke::where('karaoke_id', $karaokeId)->where('status', 'pending')->firstOrFail();

        return response()->json($karaoke);
    }

    public function show($karaokeId){
        $karaoke = Karaoke::with('unplayedSongs')->where('karaoke_id', $karaokeId)->firstOrFail();

        return response()->json($karaoke);
    }

    public function delete(Karaoke $karaoke){
        try {
            DB::beginTransaction();

            User::where('karaoke_id', $karaoke->karaoke_id)->delete();

            $karaoke->delete();

            DB::commit();

            broadcast(new RemoteControlEvent(
                $karaoke->karaoke_id,
                "delete"
            ))->toOthers();
    
            return response()->json([
                "message" => "Karaoke delete successfully"
            ]);

        } catch (\Exception $e){
            DB::rollBack();

            throw $e;
        }

    }
}
