<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        /*
         * Disable Passports migrations. We need to do this because the current production host runs
         * on a managed database server on which primary keys are enforced. Laravel currently does not allow
         * the creation of string primary keys in this environment as the queries are run in two parts. This is
         * currently a wontfix on Laravel, so we have to write our own migrations to circumvent.
         */
        Passport::ignoreMigrations();
    }
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
    }
}
