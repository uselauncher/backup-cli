<?php

use App\BackupFactory;
use App\DatabaseBackup;
use App\DatabaseDumperFactory;
use App\FilesystemFactory;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Spatie\DbDumper\DbDumper;

test('it fetches the job data and runs the backup', function () {
    Http::fake([
        'https://api.com/succeeded' => Http::response(),
        'https://api.com/backup'    => Http::response([
            'data' => [
                'database' => [
                    'driver'   => 'mysql',
                    'host'     => 'mysql-8',
                    'username' => 'root',
                    'password' => 'secret',
                    'port'     => 3306,
                ],
                'filesystem' => [
                    'driver'   => 'sftp',
                    'username' => 'root',
                ],
                'databases'     => ['my_app'],
                'path'          => '/',
                'token'         => '0123456789abcdefghijklmnopqrstuv',
                'cipher'        => 'AES-256-CBC',
                'succeeded_url' => 'https://api.com/succeeded',
                'failed_url'    => 'https://api.com/failed',
            ],
        ], 201),
    ]);

    //

    $dumperFactory = $this->mock(DatabaseDumperFactory::class);
    $dumper = $this->mock(DbDumper::class);

    $dumperFactory->shouldReceive('create')->with([
        'driver'   => 'mysql',
        'host'     => 'mysql-8',
        'username' => 'root',
        'password' => 'secret',
        'port'     => 3306,
    ])->andReturn($dumper);

    //

    $filesystemFactory = $this->mock(FilesystemFactory::class);
    $filesystem = $this->mock(FilesystemAdapter::class);

    $filesystemFactory ->shouldReceive('create')->with([
        'driver'   => 'sftp',
        'username' => 'root',
        'password' => 'secret',
    ])->andReturn($filesystem);

    //
    $backup = $this->mock(DatabaseBackup::class);
    $backup->shouldReceive('handle')->with('my_app', '/');

    $backupFactory = $this->mock(BackupFactory::class);
    $backupFactory->shouldReceive('database')->with($dumper, $filesystem)->andReturn($backup);

    //

    $this->artisan('backup:database https://api.com/backup eyJpdiI6InlCQ3p3eTRhWnRjcWZ6blZ6c2tFcGc9PSIsInZhbHVlIjoiZG9FSUQwdVJDOEJRYTZWUVlWV2ZjQ2w1Mk9sclpSWS90RExDQm5SVDRqTEppL2tmV1lXcnJmeThIMzlGb2VuLyIsIm1hYyI6ImQzYmEzODAyOWU5ZmY1MTA4OWNlYTdiMjIxNjBhMDBkZDdiZjcxNzY5ZTNjNTM4NDQ4YzExNDEwZjVlOGE4YWIiLCJ0YWciOiIifQ==')
        ->expectsOutput('Backup finished without errors.')
        ->assertExitCode(0);

    Http::assertSent(fn (Request $request) => $request->url() == 'https://api.com/succeeded');
    Http::assertNotSent(fn (Request $request) => $request->url() == 'https://api.com/failed');
});

test('it can call the failed url', function () {
    Http::fake([
        'https://api.com/failed' => Http::response(),
        'https://api.com/backup' => Http::response([
            'data' => [
                'database'      => [],
                'filesystem'    => [],
                'databases'     => ['my_app'],
                'path'          => '/',
                'token'         => '0123456789abcdefghijklmnopqrstuv',
                'cipher'        => 'AES-256-CBC',
                'succeeded_url' => 'https://api.com/succeeded',
                'failed_url'    => 'https://api.com/failed',
            ],
        ], 201),
    ]);

    //

    $this->mock(DatabaseDumperFactory::class)
        ->shouldReceive('create')
        ->andReturn($this->mock(DbDumper::class));

    //

    $this->mock(FilesystemFactory::class)
        ->shouldReceive('create')
        ->andReturn($this->mock(FilesystemAdapter::class));

    //

    $backup = $this->mock(DatabaseBackup::class)
        ->shouldReceive('handle')
        ->with('my_app', '/')
        ->andThrow(new Exception("Database not found"))
        ->getMock();

    $this->mock(BackupFactory::class)
        ->shouldReceive('database')
        ->andReturn($backup);

    //

    $this->artisan('backup:database https://api.com/backup eyJpdiI6InlCQ3p3eTRhWnRjcWZ6blZ6c2tFcGc9PSIsInZhbHVlIjoiZG9FSUQwdVJDOEJRYTZWUVlWV2ZjQ2w1Mk9sclpSWS90RExDQm5SVDRqTEppL2tmV1lXcnJmeThIMzlGb2VuLyIsIm1hYyI6ImQzYmEzODAyOWU5ZmY1MTA4OWNlYTdiMjIxNjBhMDBkZDdiZjcxNzY5ZTNjNTM4NDQ4YzExNDEwZjVlOGE4YWIiLCJ0YWciOiIifQ==')
        ->expectsOutput('Backup finished with errors:')
        ->expectsOutput('Could not backup database my_app: Database not found')
        ->assertExitCode(0);

    Http::assertNotSent(fn (Request $request) => $request->url() == 'https://api.com/succeeded');
    Http::assertSent(fn (Request $request) => $request->url() == 'https://api.com/failed');
});
