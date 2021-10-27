<?php

namespace App;

use Illuminate\Support\Arr;
use Spatie\DbDumper\Compressors\GzipCompressor;
use Spatie\DbDumper\Databases\MySql;
use Spatie\DbDumper\DbDumper;

class DatabaseDumperFactory
{
    /**
     * The config of the Dumper to initialize.
     */
    private array $config;

    /**
     * Returns a DbDumper based on the driver.
     *
     * @return \Spatie\DbDumper\DbDumper
     */
    public function create(array $config): DbDumper
    {
        $this->config = $config;

        return match (Arr::get($this->config, 'driver')) {
            'mysql' => $this->createMySql(),
        };
    }

    /**
     * Creates the MySql dumper.
     *
     * @return \Spatie\DbDumper\Databases\MySql
     */
    public function createMySql(): MySql
    {
        /** @var MySql $dumper */
        $dumper = MySql::create()
            ->setHost(Arr::get($this->config, 'host'))
            ->setPort(Arr::get($this->config, 'port'))
            ->setUserName(Arr::get($this->config, 'username'))
            ->setPassword(Arr::get($this->config, 'password'))
            ->useCompressor(new GzipCompressor);

        return $dumper
            ->useQuick()
            ->skipLockTables()
            ->useSingleTransaction();
    }
}
