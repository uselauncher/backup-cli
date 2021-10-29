<?php

use App\DatabaseBackup;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Adapter\Local;
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
        expect($filename)->toStartWith(Storage::path('my-app-'));
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

test('it can store a dump on a local filesystem', function () {
    $dumper = Mockery::mock(DbDumper::class);
    $filesystem = Mockery::mock(FilesystemAdapter::class);

    $backup = new DatabaseBackup($dumper, $filesystem);

    $dumper->shouldReceive('setDbName')->andReturnSelf();
    $dumper->shouldReceive('dumpToFile')->with(storage_path('db.zip'));

    $dumper->shouldReceive('getDbName')->andReturn('my_app');

    $filesystem->shouldReceive('getDriver')->andReturn(
        Mockery::mock(Filesystem::class)->shouldReceive('getAdapter')->andReturn(
            Mockery::mock(Local::class)
        )->getMock()
    );

    $filesystem->shouldReceive('path')->withArgs(function ($filename) {
        expect($filename)->toStartWith('my-app-');

        return true;
    })->andReturn(storage_path('db.zip'));

    $backup->handle('my_app');
});

test('it can store a dump on a filesystem with a prefixed path', function () {
    $dumper = Mockery::mock(DbDumper::class);
    $filesystem = Mockery::mock(FilesystemAdapter::class);

    Storage::fake();

    $backup = new DatabaseBackup($dumper, $filesystem);

    $dumper->shouldReceive('setDbName')->andReturnSelf();
    $dumper->shouldReceive('dumpToFile')->withArgs(function ($filename) {
        expect($filename)->toStartWith(Storage::path('my-app-'));
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

    $filesystem->shouldReceive('makeDirectory')->with('backups');

    $filesystem->shouldReceive('put')->withArgs(function ($filename, $contents) {
        expect($filename)->toStartWith('backups/my-app-');
        expect($contents)->toBeResource();

        return true;
    });

    $backup->handle('my_app', '/backups');

    expect(Storage::allFiles())->toBeEmpty();
});

test('it can store a dump on a local filesystem with a prefixed path', function () {
    $dumper = Mockery::mock(DbDumper::class);
    $filesystem = Mockery::mock(FilesystemAdapter::class);

    $backup = new DatabaseBackup($dumper, $filesystem);

    $dumper->shouldReceive('setDbName')->andReturnSelf();
    $dumper->shouldReceive('dumpToFile')->with(storage_path('backups/db.zip'));

    $dumper->shouldReceive('getDbName')->andReturn('my_app');

    $filesystem->shouldReceive('getDriver')->andReturn(
        Mockery::mock(Filesystem::class)->shouldReceive('getAdapter')->andReturn(
            Mockery::mock(Local::class)
        )->getMock()
    );

    $filesystem->shouldReceive('makeDirectory')->with('backups');

    $filesystem->shouldReceive('path')->withArgs(function ($filename) {
        expect($filename)->toStartWith('backups/my-app-');

        return true;
    })->andReturn(storage_path('backups/db.zip'));

    $backup->handle('my_app', '/backups/');
});
