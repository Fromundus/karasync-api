<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Song;
use App\Models\SongBook;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class RemoteController extends Controller
{
    public function search(Request $request)
    {
        $query = $request->input('query');

        // 🔍 1. Search in local database first
        $localResults = SongBook::where('title', 'like', '%' . $query . '%')
            ->orWhere('channel', 'like', '%' . $query . '%')
            ->get();

        // ✅ If found in DB, return immediately
        if ($localResults->isNotEmpty()) {
            return response()->json([
                'message' => 'Results found in database.',
                'data' => $localResults,
                'source' => 'database'
            ]);
        }

        $apiKey = config('services.youtube.key');

        $response = Http::get('https://www.googleapis.com/youtube/v3/search', [
            'key' => $apiKey,
            'part' => 'snippet',
            'maxResults' => 5,
            'q' => $query . 'karaoke',
            'type' => 'video'
        ]);

        $items = $response->json()['items'];

        $saved = [];
        $result = [];

        foreach ($items as $item) {
            $videoId = $item['id']['videoId'];

            // Check if song already exists
            if (!SongBook::where('code', $videoId)->exists()) {
                if($item['snippet']['channelId'] !== 'UCwTRjvjVge51X-ILJ4i22ew'){
                    $song = SongBook::create([
                        'code'      => $videoId,
                        'thumbnail' => $item['snippet']['thumbnails']['default']['url'],
                        'title'     => $item['snippet']['title'],
                        'channel'   => $item['snippet']['channelTitle'],
                        'color'     => $this->generateRandomColor(),
                        'priority'  => null,
                    ]);
                    
                    $saved[] = $song;
                }
                
            }

            if($item['snippet']['channelId'] !== 'UCwTRjvjVge51X-ILJ4i22ew'){
                $updatedSongFormat = [
                    'code'      => $videoId,
                    'thumbnail' => $item['snippet']['thumbnails']['default']['url'],
                    'title'     => $item['snippet']['title'],
                    'channel'   => $item['snippet']['channelTitle'],
                    'color'     => $this->generateRandomColor(),
                    'priority'  => null,
                ];
    
                $result[] = $updatedSongFormat;
            }
        }

        return response()->json([
            'message' => 'Results fetched from YouTube.',
            'data' => $result,
            'original_results' => $items,
            'source' => 'youtube'
        ]);
    }

    public function reserve(Request $request){
        $validated = $request->validate([
            "karaoke_id" => "required",
            "code" => "required",
            "thumbnail" => "required",
            "title" => "required",
            "channel" => "required",
            "color" => "required",
        ]);

        $song = Song::create([
            ...$validated,
            "status" => "unplayed",
        ]);

        if($song){
            // broadcast(new KaraokeControlEvent($request->karaokeKaraokeId, "songadded"));

            return response()->json([
                "message" => "successfully added",
            ], 200);
        } else {
            return response()->json([
                "message" => "failed"
            ], 500);
        }
    }

    private function generateRandomColor()
    {
        return sprintf('#%06X', mt_rand(0, 0xFFFFFF));
    }
}
