<?php

namespace App\Providers;

use App\Helpers\FileUploaderHelper;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class FileUploaderServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(FileUploaderHelper::class, function(Application $app) {
            return new FileUploaderHelper();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
