<?php

namespace App\Providers;

use App\Services\Metar\MetarRetrievalService;
use App\Services\Metar\MetarService;
use App\Services\Metar\Parser\PressureParser;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class MetarServiceProvider extends ServiceProvider
{
    public function register()
    {
        parent::register();
        $this->app->singleton(MetarService::class, function (Application $application) {
            return new MetarService(
                $application->make(MetarRetrievalService::class),
                collect([
                    $this->app->make(PressureParser::class)
                ])
            );
        });
    }
}
