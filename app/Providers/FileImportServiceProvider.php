<?php

namespace App\Providers;

use App\Helpers\FileUploaderHelper;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Foundation\Application;

class FileImportServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // $this->app->bind(FileUploaderHelper::class, function(Application $app) {
        //     return new FileUploaderHelper();
        // });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
