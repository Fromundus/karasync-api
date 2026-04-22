<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsSubscribed
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if($user->role === "user"){
            if ($user && !$user->subscription_status) {
                return response()->json(['message' => 'Unsubscribed: Account subscription expired.'], 402);
            }
        } else if ($user->role === "remote"){
            if ($user && !$user->karaoke->user->subscription_status) {
                return response()->json(['message' => 'Unsubscribed: Account subscription expired.'], 402);
            }
        }

        return $next($request);
    }
}
