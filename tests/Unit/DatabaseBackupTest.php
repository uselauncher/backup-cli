<?php

use App\DatabaseBackup;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Filesystem;
use League\Flysystem\Sftp\SftpAdapter;
use Spatie\DbDumper\DbDumper;

test('it can store a dump on a filesystem', function () {
    $dumper = Mockery::mock(DbDumper::class);
    $filesystem = Mockery::mock(FilesystemAdapter::class);

    Storage::fake();

    $backup = new DatabaseBackup($dumper, $filesystem);

    $dumper->shouldReceive('setDbName')->with('my_app')->andReturnSelf();
    $dumper->shouldReceive('dumpToFile')->withArgs(function ($filename) {
        expect($filename)->toEndWith('.sql.gz');
        file_put_contents($filename, 'contents');

        return true;
    });

    $dumper->shouldReceive('getDbName')->andReturn('my_app');

    $filesystem->shouldReceive('getDriver')->andReturn(
        Mockery::mock(Filesystem::class)->shouldReceive('getAdapter')->andReturn(
            Mockery::mock(SftpAdapter::class)
        )->getMock()
    );

    $filesystem->shouldReceive('put')->withArgs(function ($filename, $contents) {
        expect($filename)->toStartWith('my-app-');
        expect($contents)->toBeResource();

        return true;
    });

    $backup->handle('my_app');

    expect(Storage::allFiles())->toBeEmpty();
});
