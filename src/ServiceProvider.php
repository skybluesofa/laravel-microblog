<?php

namespace Skybluesofa\Microblog;

use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;

class ServiceProvider extends IlluminateServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {

        if (class_exists('CreateMicroblogPostsTable')) {
            return;
        }

        $migrationsDirectory = __DIR__ . '/database/migrations/';
        $configDirectory = __DIR__ . '/config/';
        $targetDirectory = database_path('migrations') . '/';

        $this->publishes([
            $migrationsDirectory . '2019_02_14_090000_create_microblog_posts_table.php'
                => $targetDirectory . date('Y_m_d_His', time()) . '_create_microblog_posts_table.php',
        ], 'migrations');

        $this->publishes([
            $migrationsDirectory . '2019_02_14_090000_create_microblog_journals_table.php'
                => $targetDirectory . date('Y_m_d_His', time()) . '_create_microblog_journals_table.php',
        ], 'migrations');

        $this->publishes([
            $configDirectory . 'microblog.php'
                => config_path('microblog.php'),
        ], 'config');
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
    }
}
