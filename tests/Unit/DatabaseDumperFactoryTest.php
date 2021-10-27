<?php

use App\DatabaseDumperFactory;
use Spatie\DbDumper\Databases\MySql;

test('it can create an instance from MySQL info', function () {
    /** @var MySql $dumper */
    $dumper = (new DatabaseDumperFactory)->create([
        'driver'   => 'mysql',
        'username' => 'username',
        'password' => 'password',
        'host'     => 'mysql-8',
        'port'     => 3307,
    ]);

    expect($dumper)->toBeInstanceOf(MySql::class);

    $credentialsFile = $dumper->getContentsOfCredentialsFile();

    expect($credentialsFile)->toContain("user = 'username'");
    expect($credentialsFile)->toContain("password = 'password'");
    expect($credentialsFile)->toContain("port = '3307'");
    expect($credentialsFile)->toContain("host = 'mysql-8'");
});
