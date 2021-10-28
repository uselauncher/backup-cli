<?php

namespace App\Commands;

use App\BackupFactory;
use App\DatabaseDumperFactory;
use App\FilesystemFactory;
use Illuminate\Encryption\Encrypter;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use LaravelZero\Framework\Commands\Command;
use Throwable;

class BackupDatabaseCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'backup:database {initializeJobUrl} {encryptedSecrets}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Run the backup task and copy to file storage';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(DatabaseDumperFactory $databaseDumperFactory, FilesystemFactory $filesystemFactory, BackupFactory $backupFactory)
    {
        try {
            $job = $this->post($this->argument('initializeJobUrl'))->json('data');
        } catch (Throwable $e) {
            return $this->error('Could not initialize a Backup Job from the Launcher API: ' . $e->getMessage());
        }

        $databaseBackup = $backupFactory->database(
            $databaseDumperFactory->create($job['database']),
            $filesystemFactory->create($this->getFilesystemConfig($job)),
        );

        [$withError, $withoutError] = Collection::make($job['databases'])
            ->map(function (string $database) use ($databaseBackup, $job) {
                try {
                    $databaseBackup->handle($database, $job['path']);
                } catch (Throwable $e) {
                    return "Could not backup database {$database}: " . $e->getMessage();
                }

                return false;
            })
            ->partition(fn ($error) => $error);

        if ($withError->isEmpty()) {
            $this->post($job['succeeded_url']);

            return $this->info("Backup finished without errors.");
        }

        $this->post($job['failed_url'], ['errors' => $withError->all()]);

        $this->error("Backup finished with errors:");

        $withError->each(fn ($error) => $this->info($error));
    }

    /**
     * Decrypts the encrypted secrets and merges them into the filesystem array.
     *
     * @param array $job
     * @return array
     */
    private function getFilesystemConfig(array $job): array
    {
        $encrypter = new Encrypter($job['token'], $job['cipher']);

        $secrets = $encrypter->decrypt($this->argument('encryptedSecrets'));

        return array_merge($job['filesystem'], $secrets);
    }

    /**
     * Executes a POST request with a 10s timeout.
     *
     * @param string $url
     * @param array $data
     * @return \Illuminate\Http\Client\Response
     */
    private function post(string $url, array $data = []): Response
    {
        return Http::timeout(10)->post($url, $data)->throw();
    }
}
