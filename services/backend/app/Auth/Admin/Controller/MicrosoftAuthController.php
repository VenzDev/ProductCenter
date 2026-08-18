<?php

namespace App\Auth\Admin\Controller;

use App\Auth\Admin\MicrosoftAdminResolver;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse as LaravelRedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\AbstractProvider;
use Symfony\Component\HttpFoundation\RedirectResponse;

class MicrosoftAuthController extends Controller
{
    public function __construct(private readonly MicrosoftAdminResolver $resolver) {}

    public function redirect(): RedirectResponse
    {
        /** @var AbstractProvider $provider */
        $provider = Socialite::driver('microsoft');

        return $provider->with(['prompt' => 'select_account'])->redirect();
    }

    public function callback(): JsonResponse|LaravelRedirectResponse
    {
        $admin = $this->resolver->resolve(Socialite::driver('microsoft')->user());

        if (! $admin) {
            return response()->json(['message' => 'No admin account found for this Microsoft account.'], 403);
        }

        Auth::guard('admin')->login($admin);

        return redirect()->intended('/admin');
    }

    public function logout(Request $request): LaravelRedirectResponse
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
