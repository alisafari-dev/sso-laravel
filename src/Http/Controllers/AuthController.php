<?php

namespace Asafari\LaravelKeycloakSso\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    public function create()
    {
        return view(config('keycloak-sso.views.login'));
    }

    public function redirect(): RedirectResponse
    {
        return Socialite::driver('keycloak')
            ->scopes(config('keycloak-sso.scopes', ['openid', 'profile', 'email']))
            ->redirect();
    }

    public function callback(): RedirectResponse
    {
        $keycloakUser = Socialite::driver('keycloak')->user();
        $identifier = $this->resolveIdentifier($keycloakUser);

        if (blank($identifier)) {
            abort(403, 'شناسه کاربر در اطلاعات Keycloak یافت نشد.');
        }

        $userModel = config('keycloak-sso.user.model', 'App\\Models\\User');
        $identifierColumn = config('keycloak-sso.user.identifier_column', 'personal_id');

        $localUser = $userModel::firstOrNew([$identifierColumn => $identifier]);
        $localUser->name = $keycloakUser->getName()
            ?? $keycloakUser->getNickname()
            ?? $keycloakUser->getId();

        if ($email = $keycloakUser->getEmail()) {
            $localUser->email = $email;
            $localUser->email_verified_at = now();
        } elseif (! $localUser->exists) {
            $localPart = $keycloakUser->getNickname() ?? $keycloakUser->getId();
            $domain = config('keycloak-sso.user.placeholder_email_domain', 'sso.local');
            $localUser->email = $localPart.'@'.$domain;
        }

        $localUser->save();

        Auth::login($localUser, remember: true);

        return redirect()->intended(route(config('keycloak-sso.redirect_after_login', 'dashboard')));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $postLogoutRedirectUri = config('services.keycloak.post_logout_redirect');

        if (! $postLogoutRedirectUri) {
            $base = preg_replace('#/callback/?$#', '', (string) config('services.keycloak.redirect'))
                ?: rtrim((string) config('app.url'), '/');
            $postLogoutRedirectUri = rtrim($base, '/').'/';
        }

        $logoutUrl = Socialite::driver('keycloak')->getLogoutUrl(
            $postLogoutRedirectUri,
            config('services.keycloak.client_id')
        );

        return redirect($logoutUrl);
    }

    protected function resolveIdentifier(SocialiteUser $keycloakUser): mixed
    {
        $claim = config('keycloak-sso.user.identifier_claim', 'employeeID');
        $raw = method_exists($keycloakUser, 'getRaw') ? $keycloakUser->getRaw() : [];

        return data_get($raw, $claim);
    }
}
