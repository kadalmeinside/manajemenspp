<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    /**
     * Redirect ke halaman autentikasi Google.
     */
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Proses callback dari Google setelah autentikasi.
     */
    public function callback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Exception $e) {
            Log::error('Google OAuth callback error: ' . $e->getMessage());
            return redirect()->route('login')->withErrors([
                'google' => 'Gagal melakukan login dengan Google. Silakan coba lagi.',
            ]);
        }

        // Cari user berdasarkan google_id atau email
        $user = User::where('google_id', $googleUser->getId())->first();

        if (! $user) {
            // Cari berdasarkan email yang sudah terdaftar
            $user = User::where('email', $googleUser->getEmail())->first();

            if ($user) {
                // User sudah punya akun dengan email ini — hubungkan ke Google
                $user->update([
                    'google_id'            => $googleUser->getId(),
                    'google_token'         => $googleUser->token,
                    'google_refresh_token' => $googleUser->refreshToken,
                    'avatar'               => $googleUser->getAvatar(),
                ]);
            } else {
                // Email tidak terdaftar — tolak login
                // Sistem ini berbasis undangan (siswa/wali terdaftar oleh admin),
                // sehingga user baru tidak bisa mendaftar sendiri via Google.
                return redirect()->route('login')->withErrors([
                    'google' => 'Akun dengan email ' . $googleUser->getEmail() . ' tidak terdaftar. Hubungi administrator untuk mendaftarkan akun Anda.',
                ]);
            }
        } else {
            // Update token terbaru
            $user->update([
                'google_token'         => $googleUser->token,
                'google_refresh_token' => $googleUser->refreshToken,
                'avatar'               => $googleUser->getAvatar(),
            ]);
        }

        Auth::login($user, remember: true);

        return redirect()->intended(route('dashboard'));
    }
}
