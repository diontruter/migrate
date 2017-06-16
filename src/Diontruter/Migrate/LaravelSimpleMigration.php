<?php namespace Diontruter\Migrate;

use Exception;
use /** @noinspection PhpUndefinedNamespaceInspection PhpUndefinedClassInspection */ Illuminate\Console\Command;

/** @noinspection PhpUndefinedClassInspection */
class LaravelSimpleMigration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'simplemigration:process {migrationCommand}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Perform a simple database migration. Valid commands are up, down and status.';

    /**
     * Execute the console command.
     *
     * @return int
     * @throws Exception
     */
    public function handle()
    {
        $configPath = './config/migration.php';
        $migration = new SimpleMigration($configPath);
        /** @noinspection PhpUndefinedMethodInspection */
        $migration->processCommandLine(['', $this->argument('migrationCommand')]);
        return 0;
    }

}
