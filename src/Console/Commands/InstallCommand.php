<?php

namespace Bazar\Console\Commands;

use Bazar\Database\Seeders\BazarSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class InstallCommand extends Command
{
    /**
     * The assets.
     *
     * @var array
     */
    protected $assets = [
        'resources/img' => 'vendor/bazar/img',
        'public/app.js' => 'vendor/bazar/app.js',
        'public/app.css' => 'vendor/bazar/app.css',
        'public/app.css.map' => 'vendor/bazar/app.css.map',
    ];

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bazar:install {--seed : Seed the database with fake data}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install Bazar';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $status = $this->call('migrate');

        if ($this->option('seed') && $this->laravel->environment('local')) {
            $status = $this->call('db:seed', ['--class' => BazarSeeder::class]);
        }

        File::ensureDirectoryExists(public_path('vendor/bazar'));

        foreach ($this->assets as $from => $to) {
            if (! file_exists(public_path($to))) {
                symlink(__DIR__.'/../../../'.$from, public_path($to));
            }
        }

        return $status;
    }
}
