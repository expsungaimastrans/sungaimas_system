<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
{
    $cred = $request->validate([
        'username' => 'required|string',
        'password' => 'required|string',
    ]);

    if (Auth::attempt(
        ['username' => $cred['username'], 'password' => $cred['password']],
        $request->boolean('remember')
    )) {
        $request->session()->regenerate();

        $user = Auth::user();

        if ($user->role === 'owner')   return redirect()->intended('/dashboard');
        if ($user->role === 'finance') return redirect()->intended('/finance');
        return redirect()->intended('/shipments'); // admin
    }

    return back()->withErrors([
        'username' => 'Username / password salah',
    ])->onlyInput('username');
}

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
