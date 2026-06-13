<x-guest-layout>
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <div class="text-center">
        <p class="mb-6 text-sm text-gray-600">
            {{ __('برای ورود به سیستم از حساب Keycloak خود استفاده کنید.') }}
        </p>

        <a href="{{ route(config('keycloak-sso.routes.names.redirect')) }}"
           class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
            {{ __('ورود با Keycloak') }}
        </a>
    </div>
</x-guest-layout>
