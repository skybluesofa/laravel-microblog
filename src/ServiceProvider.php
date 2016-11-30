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

        $stub      = __DIR__ . '/database/migrations/';
        $target    = database_path('migrations') . '/';

        $this->publishes([
            $stub . 'create_microblog_posts_table.php'      => $target . date('Y_m_d_His', time()) . '_create_microblog_posts_table.php',
        ], 'migrations');

        $this->publishes([
            $stub . 'create_microblog_journals_table.php'   => $target . date('Y_m_d_His', time()) . '_create_microblog_journals_table.php',
        ], 'migrations');

        $this->publishes([
            __DIR__ . '/config/microblog.php'               => config_path('microblog.php'),
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
