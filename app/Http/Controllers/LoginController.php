<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\SapUser;
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

        try {
            $response = $this->flaskApi->post('/api/sap-login', [
                'username' => $validated['sap_id'],
                'password' => $validated['password'],
            ]);

            session([
                'username' => $validated['sap_id'],
                'password' => $validated['password'],
            ]);

            // Jika otentikasi di SAP gagal
            if (!$response->successful()) {
                $errorMessage = $response->json('message', 'Username atau Password SAP tidak valid.');
                return back()->withErrors(['login' => $errorMessage]);
            }

            $sapUser = SapUser::where('sap_id', $validated['sap_id'])->first();
            if (!$sapUser) {
                return back()->withErrors(['login' => 'SAP ID ini tidak terdaftar di sistem internal.']);
            }

            $user = User::firstOrCreate(
                ['email' => $validated['sap_id'] . '@kmi.local'],
                [
                    'name' => $sapUser->nama,
                    'password' => Hash::make(Str::random(16)),
                    'role' => 'admin'
                ]
            );
            
            if ($user->role !== 'admin') {
                $user->role = 'admin';
                $user->save();
            }

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
