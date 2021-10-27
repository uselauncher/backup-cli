<?php

namespace App;

use Illuminate\Filesystem\FilesystemAdapter;
use Spatie\DbDumper\DbDumper;

class BackupFactory
{
    /**
     * Creates a DatabaseBackup instance.
     *
     * @param \Spatie\DbDumper\DbDumper $dumper
     * @param \Illuminate\Filesystem\FilesystemAdapter $filesystem
     * @return \App\DatabaseBackup
     */
    public function database(DbDumper $dumper, FilesystemAdapter $filesystem): DatabaseBackup
    {
        return new DatabaseBackup($dumper, $filesystem);
    }
}
