<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\UserSap;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Http\Client\ConnectionException;

class LoginController extends Controller
{
    protected $flaskApi;

    public function __construct()
    {
        $baseUrl = config('services.flask.base_url');
        $this->flaskApi = Http::baseUrl($baseUrl)
                             ->timeout(30);
    }
    public function checkAuth(Request $request)
    {
        if (Auth::check()) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
    return redirect('/login');
    }
    public function showLoginForm()
    {
        // Jika belum login, tampilkan halaman login
        return view('auth.login');
    }

    public function loginAdmin(Request $request)
    {
        $validated = $request->validate([
            'sap_id' => 'required|string',
            'password' => 'required|string',
        ]);

        // 1. Cek dulu di database lokal (UserSap) apakah user/nama terdaftar
        $userSapInput = $validated['sap_id'];
        $localUser = UserSap::where(function($query) use ($userSapInput) {
            $query->where('user_sap', $userSapInput)
                  ->orWhere('name', 'like', $userSapInput); // Support partial name match or exact? Assuming exact strictly or 'like' for flexibility.
                  // Let's use exact match or at least 'like' if user types 'NAME'. 
                  // But standard usually expects User ID or Exact Name. 
                  // Let's try exact match on Name first or 'user_sap' code.
        })->first();
        
        // Let's refine strictness. If user types "DWI AGUSTINA", it should match.
        if (!$localUser) {
             // Fallback: Try with 'like' query if exact match failed, for ease of use if requested, 
             // but 'like' might be dangerous if multiple users have similar names. 
             // For security, strict match on ID is best. But user mentioned "cek apakah namanya terdaftar".
             // Let's assume strict check for now to avoid ambiguity.
             $localUser = UserSap::where('name', $userSapInput)->first();
        }

        if (!$localUser) {
            return back()->withErrors(['login' => 'User tidak terdaftar di database User SAP lokal.']);
        }

        // Ambil ID SAP yang sebenarnya untuk dikirim ke Flask
        $actualSapId = $localUser->user_sap;

        try {
            // 2. Kirim login ke Flask menggunakan ID SAP yang valid
            $response = $this->flaskApi->post('/api/sap-login', [
                'username' => $actualSapId,
                'password' => $validated['password'],
            ]);

            session([
                'username' => $actualSapId,
                'password' => $validated['password'],
            ]);

            // Jika otentikasi di SAP gagal
            if (!$response->successful()) {
                $errorMessage = $response->json('message', 'Password SAP salah atau otentikasi gagal.');
                return back()->withErrors(['login' => $errorMessage]);
            }

            // 3. Login berhasil, buat/update user lokal untuk session Laravel
            $user = User::firstOrCreate(
                ['email' => $actualSapId . '@kmi.local'],
                [
                    'name' => $localUser->name, // Use name from UserSap
                    'password' => Hash::make(Str::random(16)),
                ]
            );
            
            Auth::login($user, true);
            
            // JIKA BERHASIL: Redirect ke dashboard admin
            return redirect()->route('dashboard-landing');

        } catch (ConnectionException $e) {
            Log::error('Koneksi ke API SAP Gagal: ' . $e->getMessage());
            return back()->withErrors(['login' => 'Tidak dapat terhubung ke layanan otentikasi. Hubungi administrator.']);
        }
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }
}
