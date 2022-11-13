<?php

namespace Skybluesofa\Microblog;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Skybluesofa\Microblog\Console\Commands\MakeMicroblogUser;
use Skybluesofa\Microblog\Events\User\MicroblogUserCreated;
use Skybluesofa\Microblog\Listeners\User\CreateMicroblogUserJournal;

class MicroblogServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for Microblog.
     *
     * @var array
     */
    protected $listen = [
        MicroblogUserCreated::class => [
            CreateMicroblogUserJournal::class,
        ],
    ];

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->activateListeners();
        $this->addcommands();

        if (class_exists('CreateMicroblogPostsTable')) {
            return;
        }

        $this->publishes([
            __DIR__.'/../database/migrations/' => database_path('migrations'),
        ], 'migrations');

        $this->publishes([
            __DIR__.'/../config/microblog.php' => config_path('microblog.php'),
        ], 'config');
    }

    protected function activateListeners(): void
    {
        foreach ($this->listen as $event => $listeners) {
            foreach ($listeners as $listener) {
                Event::listen($event, [$listener, 'handle']);
            }
        }
    }

    protected function addCommands(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            MakeMicroblogUser::class,
        ]);
    }
}
