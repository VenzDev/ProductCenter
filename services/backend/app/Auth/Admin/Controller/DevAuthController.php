<?php

namespace App\Auth\Admin\Controller;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

// Bypasses Microsoft Entra ID SSO for local development, where nobody has a real
// tenant account to click through. Only reachable when the route is registered
// (routes/web.php gates it on app()->environment('local')), so it never exists
// outside local dev.
class DevAuthController extends Controller
{
    public function login(): RedirectResponse
    {
        $admin = Admin::first() ?? Admin::create([
            'name' => 'Dev Admin',
            'email' => 'dev-admin@localhost',
            'microsoft_id' => 'dev-local',
        ]);

        Auth::guard('admin')->login($admin);

        return redirect('/admin');
    }
}
