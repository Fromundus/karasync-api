<?php

namespace App\Http\Controllers\Api;

use App\Events\RemoteControlEvent;
use App\Events\UserEvent;
use App\Http\Controllers\Controller;
use App\Models\Karaoke;
use App\Models\Song;
use App\Models\SongBook;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RemoteController extends Controller
{
    public function search(Request $request)
    {
        // $query = $request->input('query');

        // $words = array_filter(explode(' ', $query));

        // $localResults = SongBook::query()
        //     ->where(function ($q) use ($words) {
        //         foreach ($words as $word) {
        //             $q->orWhere('title', 'like', "%{$word}%");
        //             // ->orWhere('channel', 'like', "%{$word}%");
        //         }
        //     })
        //     ->orderBy('title')
        //     ->limit(100)
        //     ->get();

        $query = $request->input('query');

        if (empty($query)) {
            $localResults = SongBook::orderByDesc('created_at')
                ->limit(100)
                ->get();

            return response()->json([
                'message' => 'Showing latest songs.',
                'data' => $localResults,
                'source' => 'database'
            ]);
        }

        $words = array_values(array_filter(explode(' ', $query)));

        // Build match score (+1 per word)
        $scoreSql = [];
        $scoreBindings = [];

        foreach ($words as $word) {
            $scoreSql[] = "CASE WHEN title LIKE ? THEN 1 ELSE 0 END";
            $scoreBindings[] = "%{$word}%";
        }

        $scoreExpression = implode(' + ', $scoreSql);

        // Build ordered pattern: %word1%word2%word3%
        $orderedPattern = '%' . implode('%', $words) . '%';

        $localResults = SongBook::query()
            ->where(function ($q) use ($words) {
                foreach ($words as $word) {
                    $q->orWhere('title', 'like', "%{$word}%");
                }
            })
            ->orderByRaw("
                (
                    CASE WHEN title = ? THEN 100 ELSE 0 END +              -- exact match
                    CASE WHEN title LIKE ? THEN 50 ELSE 0 END +            -- starts with
                    CASE WHEN title LIKE ? THEN 30 ELSE 0 END +            -- full phrase
                    CASE WHEN title LIKE ? THEN 20 ELSE 0 END +            -- correct order
                    ($scoreExpression)                                     -- word matches
                ) DESC
            ", array_merge(
                [
                    $query,
                    "{$query}%",
                    "%{$query}%",
                    $orderedPattern
                ],
                $scoreBindings
            ))
            // ->orderBy('title')
            ->limit(100)
            ->get();

        // ✅ If found in DB, return immediately
        if ($localResults->isNotEmpty()) {
            return response()->json([
                'message' => 'Results found in database.',
                'data' => $localResults,
                'source' => 'database'
            ]);
        }
        else {
            $result = $this->searchRender($query);

            return response()->json([
                'message' => 'Results fetched from YouTube.',
                'data' => $result["data"],
                'original_results' => $result["original_results"],
                'source' => 'youtube'
            ]);
        }
    }

    //ORIGINAL SEARCH

    // public function search(Request $request)
    // {
    //     $query = $request->input('query');

    //     // 🔍 1. Search in local database first
    //     $localResults = SongBook::where('title', 'like', '%' . $query . '%')
    //         ->orWhere('channel', 'like', '%' . $query . '%')
    //         ->limit(100)
    //         ->orderBy('title')
    //         ->get();

    //     // ✅ If found in DB, return immediately
    //     if ($localResults->isNotEmpty()) {
    //         return response()->json([
    //             'message' => 'Results found in database.',
    //             'data' => $localResults,
    //             'source' => 'database'
    //         ]);
    //     } else {
    //         $result = $this->searchYoutube($query);

    //         return response()->json([
    //             'message' => 'Results fetched from YouTube.',
    //             'data' => $result["data"],
    //             'original_results' => $result["original_results"],
    //             'source' => 'youtube'
    //         ]);
    //     }
    // }

    public function youtubeSearch(Request $request){
        $query = $request->input('query');

        // $result = $this->searchYoutube($query);
        $result = $this->searchRender($query);

        return response()->json([
            'message' => 'Results fetched from YouTube.',
            'data' => $result["data"],
            'original_results' => $result["original_results"],
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

        $karaoke = Karaoke::findOrFail($validated["karaoke_id"]);

        if($song){
            // broadcast(new KaraokeControlEvent($request->karaokeKaraokeId, "songadded"));
            broadcast(new RemoteControlEvent(
                $karaoke->karaoke_id,
                "reserve"
            ))->toOthers();

            return response()->json([
                "message" => "successfully added",
            ], 200);
        } else {
            return response()->json([
                "message" => "failed"
            ], 500);
        }
    }

    public function next(Request $request){
        $validated = $request->validate([
            "karaoke_id" => "required", // karaoke.id
            "song_id" => "required",
        ]);

        $song = Song::where("id", $validated["song_id"])->where("karaoke_id", $validated["karaoke_id"])->firstOrFail();

        $song->update([
            "status" => "played"
        ]);

        $karaoke = Karaoke::with('user')->findOrFail($validated["karaoke_id"]);

        $user = $karaoke->user;

        broadcast(new RemoteControlEvent(
            $karaoke->karaoke_id,
            "stop"
        ))->toOthers();

        broadcast(new UserEvent(
            $user->id,
            "fetch"
        ))->toOthers();

        return response()->json([
            "message" => "success"
        ], 200);
    }

    public function stopAll(Request $request){
        $validated = $request->validate([
            "karaoke_id" => "required", // karaoke.id
        ]);

        Song::where("karaoke_id", $validated["karaoke_id"])->update([
            "status" => "played",
        ]);

        $karaoke = Karaoke::findOrFail($validated["karaoke_id"]);

        broadcast(new RemoteControlEvent(
            $karaoke->karaoke_id,
            "stop"
        ))->toOthers();

        return response()->json([
            "message" => "success"
        ], 200);
    }

    public function remote(Request $request)
    {
        broadcast(new RemoteControlEvent(
            $request->karaoke_id,
            $request->action
        ))->toOthers();

        return response()->json(['ok' => true]);
    }

    private function generateRandomColor()
    {
        return sprintf('#%06X', mt_rand(0, 0xFFFFFF));
    }

    private function searchYoutube($query){
        $apiKey = config('services.youtube.key');

        $cacheKey = 'yt_search_' . md5($query);

        // $response = Cache::remember($cacheKey, now()->addHours(5), function () use ($apiKey, $query) {
        //     $res = Http::get('https://www.googleapis.com/youtube/v3/search', [
        //         'key' => $apiKey,
        //         'part' => 'snippet',
        //         'maxResults' => 50,
        //         'q' => $query . ' karaoke OR "karaoke songs" OR "karaoke hits" OR "karaoke playlist"',
        //         'type' => 'video',
        //         'videoCategoryId' => '10', // 🎵 Music category
        //         'order' => 'relevance', // or 'viewCount'
        //         // 'regionCode' => 'PH',
        //     ])->json();

        //     if ($res->failed()) {
        //         return ['items' => []];
        //     }

        //     return $res->json();
        // });

        $response = Cache::remember($cacheKey, now()->addHours(5), function () use ($apiKey, $query) {
            $res = Http::get('https://www.googleapis.com/youtube/v3/search', [
                'key' => $apiKey,
                'part' => 'snippet',
                'maxResults' => 50,
                'q' => $query . ' karaoke OR "karaoke songs" OR "karaoke hits" OR "karaoke playlist"',
                'type' => 'video',
                'videoCategoryId' => '10',
                'order' => 'relevance',
            ]);

            if ($res->failed()) {
                return ['items' => []];
            }

            return $res->json();
        });

        $items = $response['items'];

        $saved = [];
        $result = [];

        foreach ($items as $item) {
            $videoId = $item['id']['videoId'];
            $title = $item['snippet']['title'];
            
            // ✅ Check if title contains "karaoke" (case-insensitive)
            $hasKaraoke = stripos($title, 'karaoke') !== false;

            if (!$hasKaraoke) {
                continue;
            }

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

        return [
            'data' => $result,
            'original_results' => $items,
        ];
    }

    private function searchRender($query){
        $youtubeServiceUrl = config('services.youtube_service_render.key');

        $cacheKey = 'yt_search_' . md5($query);

        $response = Cache::remember($cacheKey, now()->addHours(5), function () use ($youtubeServiceUrl, $query) {
            $res = Http::timeout(60)->retry(3, 2000)->get($youtubeServiceUrl . '/search?q='. $query . 'karaoke OR "karaoke songs" OR "karaoke hits" OR "karaoke playlist"');
            
            if ($res->failed()) {
                return ['items' => []];
            }

            return $res->json();
        });

        // Log::info($response);

        $items = $response['items'];

        $saved = [];
        $result = [];

        foreach ($items as $item) {
            $videoId = $item['id'];
            $title = $item['title'];
            $channelTitle = $item['channelTitle'];
            $thumbnail = $item['thumbnail']['thumbnails'][0]['url'];
            
            // ✅ Check if title contains "karaoke" (case-insensitive)
            $hasKaraoke = stripos($title, 'karaoke') !== false;

            if (!$hasKaraoke) {
                continue;
            }

            if (!SongBook::where('code', $videoId)->exists()) {
                if($channelTitle !== 'Sing King'){
                    $song = SongBook::create([
                        'code'      => $videoId,
                        'thumbnail' => $thumbnail,
                        'title'     => $title,
                        'channel'   => $channelTitle,
                        'color'     => $this->generateRandomColor(),
                        'priority'  => null,
                    ]);
                    
                    $saved[] = $song;
                }
                
            }

            if($channelTitle !== 'Sing King'){
                $updatedSongFormat = [
                    'code'      => $videoId,
                    'thumbnail' => $thumbnail,
                    'title'     => $title,
                    'channel'   => $channelTitle,
                    'color'     => $this->generateRandomColor(),
                    'priority'  => null,
                ];
    
                $result[] = $updatedSongFormat;
            }
        }

        return [
            'data' => $result,
            'original_results' => $items,
        ];
    }

    //ORIGINAL SEARCH YOUTUBE

    // private function searchYoutube($query){
    //     $apiKey = config('services.youtube.key');

    //     $response = Http::get('https://www.googleapis.com/youtube/v3/search', [
    //         'key' => $apiKey,
    //         'part' => 'snippet',
    //         'maxResults' => 5,
    //         'q' => $query . 'karaoke',
    //         'type' => 'video'
    //     ]);

    //     $items = $response->json()['items'];

    //     $saved = [];
    //     $result = [];

    //     foreach ($items as $item) {
    //         $videoId = $item['id']['videoId'];

    //         // Check if song already exists
    //         if (!SongBook::where('code', $videoId)->exists()) {
    //             if($item['snippet']['channelId'] !== 'UCwTRjvjVge51X-ILJ4i22ew'){
    //                 $song = SongBook::create([
    //                     'code'      => $videoId,
    //                     'thumbnail' => $item['snippet']['thumbnails']['default']['url'],
    //                     'title'     => $item['snippet']['title'],
    //                     'channel'   => $item['snippet']['channelTitle'],
    //                     'color'     => $this->generateRandomColor(),
    //                     'priority'  => null,
    //                 ]);
                    
    //                 $saved[] = $song;
    //             }
                
    //         }

    //         if($item['snippet']['channelId'] !== 'UCwTRjvjVge51X-ILJ4i22ew'){
    //             $updatedSongFormat = [
    //                 'code'      => $videoId,
    //                 'thumbnail' => $item['snippet']['thumbnails']['default']['url'],
    //                 'title'     => $item['snippet']['title'],
    //                 'channel'   => $item['snippet']['channelTitle'],
    //                 'color'     => $this->generateRandomColor(),
    //                 'priority'  => null,
    //             ];
    
    //             $result[] = $updatedSongFormat;
    //         }
    //     }

    //     return [
    //         'data' => $result,
    //         'original_results' => $items,
    //     ];
    // }
}
