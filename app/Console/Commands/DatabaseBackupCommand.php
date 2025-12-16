<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class DatabaseBackupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:db-backup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a backup of the application database';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $db_name = config('database.connections.pgsql.database');
        $db_user = config('database.connections.pgsql.username');
        $db_password = config('database.connections.pgsql.password');
        $db_host = config('database.connections.pgsql.host');
        $db_port = config('database.connections.pgsql.port', 5432);

        $backup_path = storage_path('backups/' . $db_name . '_' . now()->format('Y-m-d_H-i-s') . '.sql');
        File::ensureDirectoryExists(storage_path('backups'));

        $command = "PGPASSWORD='$db_password' pg_dump --no-owner -h $db_host -p $db_port -U $db_user $db_name > $backup_path";
        exec($command, $output, $returnVar);
        if ($returnVar !== 0) {
            $this->error('Database backup command failed: ' . implode("\n", $output));
            $this->error('Database backup failed!');
            return 1; // Command::FAILURE
        }
        $this->info('Database backup created at ' . $backup_path);
        $this->info('Backup command executed successfully.');

        // Clearing old backups
        $files = File::files(storage_path('backups'));
        $files = collect($files)->sortByDesc(function ($file) {
            return $file->getMTime();
        });
        $files->skip(1)->each(function ($file) {
            File::delete($file);
            $this->info('Deleted old backup: ' . $file->getFilename());
        });
        $this->info('Old backups cleared successfully.');

        return 0; // Command::SUCCESS

    }
}
