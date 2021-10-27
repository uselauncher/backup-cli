<?php

namespace App;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use League\Flysystem\WebDAV\WebDAVAdapter;
use Spatie\DbDumper\DbDumper;

class DatabaseBackup
{
    public function __construct(private DbDumper $dumper, private FilesystemAdapter $filesystem)
    {
    }

    /**
     * Returns a boolean whether the filesystem is a Local driver.
     *
     * @return boolean
     */
    private function isLocal(): bool
    {
        /** @var Filesystem $driver */
        $driver = $this->filesystem->getDriver();

        return $driver->getAdapter() instanceof Local;
    }

    /**
     * Returns a boolean whether the filesystem is a WebDAV driver.
     *
     * @return boolean
     */
    private function isWebdav(): bool
    {
        /** @var Filesystem $driver */
        $driver = $this->filesystem->getDriver();

        return $driver->getAdapter() instanceof WebDAVAdapter;
    }

    /**
     * Dumps the database to the given filesystem.
     *
     * @param string $database
     * @return string
     */
    public function handle(string $database): string
    {
        $filename = Str::slug($database) . '-' . now()->format('Y-m-d-H-i-s') . '.sql.gz';

        $isLocal = $this->isLocal();

        try {
            $this->dumper->setDbName($database)->dumpToFile(
                $isLocal ? $this->filesystem->path($filename) : Storage::path($filename)
            );

            if (!$isLocal) {
                $this->filesystem->put(
                    $filename,
                    $this->isWebdav() ? Storage::get($filename) : Storage::readStream($filename)
                );
            }
        } finally {
            if (!$isLocal) {
                Storage::delete($filename);
            }
        }

        return $filename;
    }
}
