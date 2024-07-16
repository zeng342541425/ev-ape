<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // 版本低於 5.7.7 的 MySQL 或者版本低於 10.2.2 的 MariaDB 上創建索引，需要手動配置數據庫遷移的默認字符串長度
        Schema::defaultStringLength(191);
    }
}
