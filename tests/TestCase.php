<?php

abstract class TestCase extends Illuminate\Foundation\Testing\TestCase
{
    /**
     * The base URL to use while testing the application.
     *
     * @var string
     */
    protected $baseUrl = 'http://localhost';

    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        $app = require __DIR__.'/../vendor/laravel/laravel/bootstrap/app.php';

        $app->register('Skybluesofa\Microblog\ServiceProvider');

        $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

        require_once(__DIR__.'/stubs/Stub_User.php');

        $app->make(\Illuminate\Database\Eloquent\Factory::class)->load(__DIR__.'/../src/database/factories');

        return $app;
    }

    /**
     * Setup DB before each test.
     */
    public function setUp()
    {
        parent::setUp();

        $this->migrate();
    }

    /**
     * run package database migrations.
     */
    public function migrate()
    {
        $this->app['config']->set('microblog.tables.microblog_journals', 'microblog_journals');
        $this->app['config']->set('microblog.tables.microblog_posts', 'microblog_posts');

        $fileSystem = new Illuminate\Filesystem\Filesystem();
        $classFinder = new Illuminate\Filesystem\ClassFinder();

        foreach ($fileSystem->files(__DIR__.'/../src/database/migrations') as $file) {
            $fileSystem->requireOnce($file);
            $migrationClass = $classFinder->findClass($file);

            (new $migrationClass())->up();
        }
    }

}
