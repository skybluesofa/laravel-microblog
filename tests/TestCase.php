<?php

namespace Skybluesofa\Microblog\Tests;

use Illuminate\Database\Eloquent\Factory;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Skybluesofa\Microblog\MicroblogServiceProvider;

abstract class TestCase extends BaseTestCase
{
    protected $enablesPackageDiscoveries = true;

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        $app->make(Factory::class)->load(__DIR__.'/../database/factories');

        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('microblog.tables.microblog_posts', 'microblog_posts');
        $app['config']->set('microblog.tables.microblog_journals', 'microblog_journals');
        $app['config']->set('microblog.tables.microblog_images', 'microblog_images');
        $app['config']->set('microblog.tables.microblog_post_images', 'microblog_post_images');
    }

    /**
     * Setup DB before each test.
     */
    public function setUp(): void
    {
        parent::setUp();

        require_once __DIR__.'/stubs/Stub_User.php';

        $this->loadLaravelMigrations();
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->withFactories(__DIR__.'/../database/factories');
    }

    protected function getPackageProviders($app)
    {
        return [
            MicroblogServiceProvider::class,
        ];
    }

    public function logout($driver = null)
    {
        $this->app['auth']->guard($driver)->logout();

        $this->app['auth']->shouldUse($driver);

        return $this;
    }
}
