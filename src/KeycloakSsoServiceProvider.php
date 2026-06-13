<?php

namespace Asafari\LaravelKeycloakSso;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use SocialiteProviders\Keycloak\Provider;
use SocialiteProviders\Manager\SocialiteWasCalled;

class KeycloakSsoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/keycloak-sso.php', 'keycloak-sso');

        $this->app->booting(function (): void {
            config([
                'services.keycloak' => array_merge(
                    config('services.keycloak', []),
                    config('keycloak-sso.keycloak', [])
                ),
            ]);
        });
    }

    public function boot(): void
    {
        $this->registerSocialiteDriver();
        $this->registerRoutes();
        $this->registerViews();
        $this->registerPublishing();
    }

    protected function registerSocialiteDriver(): void
    {
        Event::listen(function (SocialiteWasCalled $event): void {
            $event->extendSocialite('keycloak', Provider::class);
        });
    }

    protected function registerRoutes(): void
    {
        if (! config('keycloak-sso.routes.enabled', true)) {
            return;
        }

        $routes = config('keycloak-sso.routes');

        Route::middleware($routes['middleware'] ?? ['web'])
            ->prefix($routes['prefix'] ?? 'sso')
            ->group(function () use ($routes): void {
                Route::middleware($routes['guest_middleware'] ?? ['guest'])->group(function () use ($routes): void {
                    Route::get($routes['paths']['login'], [Http\Controllers\AuthController::class, 'create'])
                        ->name($routes['names']['login']);

                    Route::get($routes['paths']['redirect'], [Http\Controllers\AuthController::class, 'redirect'])
                        ->name($routes['names']['redirect']);

                    Route::get($routes['paths']['callback'], [Http\Controllers\AuthController::class, 'callback'])
                        ->name($routes['names']['callback']);
                });

                Route::middleware($routes['auth_middleware'] ?? ['auth'])->group(function () use ($routes): void {
                    Route::post($routes['paths']['logout'], [Http\Controllers\AuthController::class, 'logout'])
                        ->name($routes['names']['logout']);
                });
            });
    }

    protected function registerViews(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'keycloak-sso');
    }

    protected function registerPublishing(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../config/keycloak-sso.php' => config_path('keycloak-sso.php'),
        ], 'keycloak-sso-config');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/keycloak-sso'),
        ], 'keycloak-sso-views');

        $this->publishes([
            __DIR__.'/../database/migrations/add_personal_id_to_users_table.php' => database_path('migrations/'.date('Y_m_d_His').'_add_personal_id_to_users_table.php'),
        ], 'keycloak-sso-migrations');
    }
}
