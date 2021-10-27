<?php

namespace App;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Support\Arr;

class FilesystemFactory
{
    /**
     * The config of the Filesystem to initialize.
     */
    private array $config;

    public function __construct(private FilesystemManager $filesystemManager)
    {
    }

    /**
     * Returns a FilesystemAdapter based on the driver.
     *
     * @return \Illuminate\Filesystem\FilesystemAdapter
     */
    public function create(array $config): FilesystemAdapter
    {
        $this->config = $config;

        return match (Arr::get($this->config, 'driver')) {
            'dropbox' => $this->createDropbox(),
            'ftp'     => $this->createFtp(),
            'local'   => $this->createLocal(),
            's3'      => $this->createS3(),
            'sftp'    => $this->createSftp(),
            'webdav'  => $this->createWebdav()
        };
    }

    /**
     * Creates the Dropbox driver.
     *
     * @return \Illuminate\Filesystem\FilesystemAdapter
     */
    private function createDropbox(): FilesystemAdapter
    {
        return $this->filesystemManager->build([
            'driver'         => 'dropbox',
            'token'          => Arr::get($this->config, 'token'),
            'case_sensitive' => false,
        ]);
    }

    /**
     * Creates the FTP driver.
     *
     * @return \Illuminate\Filesystem\FilesystemAdapter
     */
    private function createFtp(): FilesystemAdapter
    {
        return $this->filesystemManager->build([
            'driver'   => 'ftp',
            'host'     => Arr::get($this->config, 'host'),
            'username' => Arr::get($this->config, 'username'),
            'password' => Arr::get($this->config, 'password'),
        ]);
    }

    /**
     * Creates the Local driver.
     *
     * @return \Illuminate\Filesystem\FilesystemAdapter
     */
    private function createLocal(): FilesystemAdapter
    {
        return $this->filesystemManager->build([
            'driver' => 'local',
            'root'   => Arr::get($this->config, 'root'),
        ]);
    }

    /**
     * Creates the SFTP driver.
     *
     * @return \Illuminate\Filesystem\FilesystemAdapter
     */
    private function createSftp(): FilesystemAdapter
    {
        $config = [
            'driver'   => 'sftp',
            'host'     => Arr::get($this->config, 'host'),
            'username' => Arr::get($this->config, 'username'),
        ];

        if ($password = Arr::get($this->config, 'password')) {
            $config['password'] = $password;
        } else {
            $config['privateKey'] = Arr::get($this->config, 'private_key');
        }

        return $this->filesystemManager->build($config);
    }

    /**
     * Creates the S3 driver.
     *
     * @return \Illuminate\Filesystem\FilesystemAdapter
     */
    private function createS3(): FilesystemAdapter
    {
        return $this->filesystemManager->build([
            'driver'   => 's3',
            'key'      => Arr::get($this->config, 'key'),
            'secret'   => Arr::get($this->config, 'secret'),
            'bucket'   => Arr::get($this->config, 'bucket'),
            'region'   => Arr::get($this->config, 'region') ?: 'us-west-1',
            'endpoint' => Arr::get($this->config, 'endpoint'),
        ]);
    }

    /**
     * Creates the WebDAV driver.
     *
     * @return \Illuminate\Filesystem\FilesystemAdapter
     */
    private function createWebdav(): FilesystemAdapter
    {
        return $this->filesystemManager->build([
            'driver'     => 'webdav',
            'baseUri'    => Arr::get($this->config, 'url'),
            'userName'   => Arr::get($this->config, 'username'),
            'password'   => Arr::get($this->config, 'password'),
            'pathPrefix' => Arr::get($this->config, 'prefix'),
        ]);
    }
}
