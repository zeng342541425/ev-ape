<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        //

        parent::boot();
    }

    /**
     * @return void
     */
    public function map()
    {
        $this->mapApiRoutes();

        $this->mapAdminRoutes();

        $this->mapWebRoutes();

        $this->mapWebSocketRoutes();

    }

    /**
     * Api
     * @return void
     */
    protected function mapApiRoutes()
    {
        Route::prefix('api')
            ->middleware('api')
            ->group(base_path('routes/api.php'));
    }

    /**
     * Admin
     * @return void
     */
    protected function mapAdminRoutes()
    {
        Route::prefix('admin')
            ->middleware('admin')
            ->group(base_path('routes/admin.php'));
    }


    /**
     * Web
     * @return void
     */
    protected function mapWebRoutes()
    {
        Route::middleware('web')
            ->group(base_path('routes/web.php'));
    }

    /**
     * WebSocket
     * @return void
     */
    protected function mapWebSocketRoutes()
    {
        Route::prefix('websocket')
            ->name('websocket.')
            ->group(base_path('routes/websocket.php'));
    }

}
