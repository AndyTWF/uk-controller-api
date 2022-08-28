<?php


namespace App\Providers;


use App\Services\Ecfmp\Downloader\ApiRequest\FlightInformationRegionsRequest;
use App\Services\Ecfmp\Downloader\ApiRequest\FlowMeasuresRequest;
use App\Services\Ecfmp\Downloader\EcfmpDataDownloader;
use Illuminate\Support\ServiceProvider;

class EcfmpServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->app->singleton(
            EcfmpDataDownloader::class,
            fn() => new EcfmpDataDownloader(
                $this->app->make(FlowMeasuresRequest::class), $this->app->make(FlightInformationRegionsRequest::class)
            )
        );
    }
}
