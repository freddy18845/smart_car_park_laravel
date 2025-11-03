<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class ReverbForcePortProvider extends ServiceProvider
{
    public function register(): void
    {
        // Force Reverb to use 8090
        config([
            'reverb.server.host' => '10.0.2.2',
            'reverb.server.port' => 8090,
        ]);
    }

    public function boot(): void
    {
        //
    }
}
