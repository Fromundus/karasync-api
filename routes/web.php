<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;
use Laravel\Socialite\Facades\Socialite;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/auth/{provider}/redirect', function ($provider) {
    return Socialite::driver($provider)->stateless()->redirect();
});

Route::get('/auth/{provider}/callback', function ($provider) {
    $socialUser = Socialite::driver($provider)->stateless()->user();

    $user = User::updateOrCreate([
        'email' => $socialUser->getEmail(),
    ], [
        'name' => $socialUser->getName() ?? $socialUser->getNickname(),
        'provider' => $provider,
        'provider_id' => $socialUser->getId(),
        'avatar' => $socialUser->getAvatar(),
    ]);

    // Option A: Sanctum
    $token = $user->createToken('auth_token')->plainTextToken;

    return redirect(config('app.frontend_url') . '/auth/callback?token=' . $token);

    // return redirect(env('FRONTEND_URL') . '/auth/callback?token=' . $token);

    // return redirect(env('FRONTEND_URL') . "/{$user->role}");
});