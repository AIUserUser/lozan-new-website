<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function show()
    {
        if (Auth::check() && Auth::user()->is_admin) {
            return redirect()->route('admin.orders');
        }

        return view('admin.login');
    }

    public function store(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if (! Auth::attempt($credentials, true)) {
            return back()->withInput()->withErrors(['email' => lozan_t('admin.login.errGeneric')]);
        }

        $request->session()->regenerate();
        if (! Auth::user()->is_admin) {
            Auth::logout();

            return back()->withErrors(['email' => lozan_t('admin.login.errGeneric')]);
        }

        return redirect()->intended(route('admin.orders'));
    }

    public function destroy(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
