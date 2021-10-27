<?php

namespace App\Providers;

use App\FilesystemFactory;
use Illuminate\Contracts\Filesystem\Factory as FactoryContract;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\Filesystem;
use Spatie\Dropbox\Client as DropboxClient;
use Spatie\FlysystemDropbox\DropboxAdapter;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Storage::extend('dropbox', function ($app, $config) {
            $client = new DropboxClient($config['token']);

            return new Filesystem(new DropboxAdapter($client));
        });

        $this->app->singleton(FilesystemFactory::class, function ($app) {
            return new FilesystemFactory($app->make(FactoryContract::class));
        });
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
