<?php

namespace App;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use League\Flysystem\Adapter\Local;
use League\Flysystem\AdapterInterface;
use League\Flysystem\Filesystem;
use League\Flysystem\WebDAV\WebDAVAdapter;
use Spatie\DbDumper\DbDumper;

class DatabaseBackup
{
    public function __construct(private DbDumper $dumper, private FilesystemAdapter $filesystem)
    {
    }

    /**
     * Returns the adapter instance of the filesystem.
     *
     * @return \League\Flysystem\AdapterInterface
     */
    private function getFilesystemAdapter(): AdapterInterface
    {
        /** @var Filesystem $driver */
        $driver = $this->filesystem->getDriver();

        return $driver->getAdapter();
    }

    /**
     * Returns a boolean whether the filesystem is a Local driver.
     *
     * @return boolean
     */
    private function isLocal(): bool
    {
        return $this->getFilesystemAdapter() instanceof Local;
    }

    /**
     * Returns a boolean whether the filesystem is a WebDAV driver.
     *
     * @return boolean
     */
    private function isWebdav(): bool
    {
        return $this->getFilesystemAdapter() instanceof WebDAVAdapter;
    }

    /**
     * Dumps the database to the given filesystem.
     *
     * @param string $database
     * @param string $path
     * @return string
     */
    public function handle(string $database, string $path = '/'): string
    {
        $filename = Str::slug($database) . '-' . now()->format('Y-m-d-H-i-s') . '.sql.gz';

        $normalizedPath = ltrim(rtrim($path, '/'), '/');

        if ($normalizedPath) {
            $this->filesystem->makeDirectory($normalizedPath);
        }

        $fullPath = $normalizedPath ? "{$normalizedPath}/{$filename}" : $filename;

        $isLocal = $this->isLocal();

        try {
            $this->dumper->setDbName($database)->dumpToFile(
                $isLocal ? $this->filesystem->path($fullPath) : Storage::path($filename)
            );

            if (!$isLocal) {
                $this->filesystem->put(
                    $fullPath,
                    $this->isWebdav() ? Storage::get($filename) : Storage::readStream($filename)
                );
            }
        } finally {
            if (!$isLocal) {
                Storage::delete($filename);
            }
        }

        return $path;
    }
}
