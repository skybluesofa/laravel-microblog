<?php

namespace Skybluesofa\Microblog\Console\Commands;

use App\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class MakeMicroblogUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:microblog-user {first_name} {last_name} {email} {password}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new user for the microblog';

    const USER_EXISTS = 'A user with that email address already exists.';

    const USER_CREATED_SUCCESSFULLY = 'User created successfully.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $emailAddress = $this->argument('email');
        if (User::where('email', $emailAddress)->count() > 0) {
            $this->error(self::USER_EXISTS);
            Log::error(self::USER_EXISTS, ['email_address' => $emailAddress]);

            return Command::FAILURE;
        }

        $user = new User();
        $user->name = $this->argument('first_name').' '.$this->argument('last_name');
        $user->first_name = $this->argument('first_name');
        $user->last_name = $this->argument('last_name');
        $user->password = Hash::make($this->argument('password'));
        $user->email = $emailAddress;
        $user->save();

        $this->info(self::USER_CREATED_SUCCESSFULLY);

        return Command::SUCCESS;
    }
}
