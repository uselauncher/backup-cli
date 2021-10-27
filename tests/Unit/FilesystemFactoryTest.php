<?php

use App\FilesystemFactory;
use League\Flysystem\Adapter\Ftp;
use League\Flysystem\Adapter\Local;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use League\Flysystem\Filesystem;
use League\Flysystem\Sftp\SftpAdapter;
use League\Flysystem\WebDAV\WebDAVAdapter;
use Spatie\FlysystemDropbox\DropboxAdapter;

test('it can build a dropbox filesystem instance', function () {
    $filesystem = app(FilesystemFactory::class)->create([
        'driver' => 'dropbox',
        'token'  => 'secret',
    ]);

    /** @var Filesystem $driver */
    $driver = $filesystem->getDriver();

    /** @var DropboxAdapter $adapter */
    $adapter = $driver->getAdapter();

    expect($adapter)->toBeInstanceOf(DropboxAdapter::class);
    expect($adapter->getClient()->getAccessToken())->toBe('secret');
});

test('it can build a ftp filesystem instance', function () {
    $filesystem = app(FilesystemFactory::class)->create([
        'driver'   => 'ftp',
        'host'     => 'host',
        'username' => 'username',
        'password' => 'password',
    ]);

    /** @var Filesystem $driver */
    $driver = $filesystem->getDriver();

    /** @var Ftp $adapter */
    $adapter = $driver->getAdapter();

    expect($adapter)->toBeInstanceOf(Ftp::class);
});

test('it can build a local filesystem instance', function () {
    $filesystem = app(FilesystemFactory::class)->create([
        'driver' => 'local',
        'root'   => storage_path(),
    ]);

    /** @var Filesystem $driver */
    $driver = $filesystem->getDriver();

    /** @var Local $adapter */
    $adapter = $driver->getAdapter();

    expect($adapter)->toBeInstanceOf(Local::class);
    expect($adapter->getPathPrefix())->toStartWith(storage_path());
});

test('it can build a s3 filesystem instance', function () {
    $filesystem = app(FilesystemFactory::class)->create([
        'driver'   => 's3',
        'key'      => 'key',
        'secret'   => 'secret',
        'bucket'   => 'bucket',
        'region'   => 'region',
        'endpoint' => 'endpoint',
    ]);

    /** @var Filesystem $driver */
    $driver = $filesystem->getDriver();

    /** @var AwsS3Adapter $adapter */
    $adapter = $driver->getAdapter();

    expect($adapter)->toBeInstanceOf(AwsS3Adapter::class);
});

test('it can build a sftp filesystem instance with a password', function () {
    $filesystem = app(FilesystemFactory::class)->create([
        'driver'          => 'sftp',
        'host'            => 'host',
        'username'        => 'username',
        'password'        => 'password',
        'use_private_key' => false,
    ]);

    /** @var Filesystem $driver */
    $driver = $filesystem->getDriver();

    /** @var SftpAdapter $adapter */
    $adapter = $driver->getAdapter();

    expect($adapter)->toBeInstanceOf(SftpAdapter::class);
    expect($adapter->getPassword())->not->toBeNull();
});

test('it can build a sftp filesystem instance with a sshkey', function () {
    $filesystem = app(FilesystemFactory::class)->create([
        'driver'          => 'sftp',
        'host'            => 'host',
        'username'        => 'username',
        'use_private_key' => true,
    ]);

    /** @var Filesystem $driver */
    $driver = $filesystem->getDriver();

    /** @var SftpAdapter $adapter */
    $adapter = $driver->getAdapter();

    expect($adapter)->toBeInstanceOf(SftpAdapter::class);
    expect($adapter->getPassword())->toBeNull();
});

test('it can build a webdav filesystem instance', function () {
    $filesystem = app(FilesystemFactory::class)->create([
        'driver'   => 'webdav',
        'url'      => 'https://mywebdavstorage.com',
        'username' => 'protonemedia',
        'password' => 'supersecretpassword',
        'prefix'   => 'backups', // optional
    ]);

    /** @var Filesystem $driver */
    $driver = $filesystem->getDriver();

    /** @var WebDAVAdapter $adapter */
    $adapter = $driver->getAdapter();

    expect($adapter)->toBeInstanceOf(WebDAVAdapter::class);
});
