<?php
use Illuminate\Database\Capsule\Manager as Capsule;

require __DIR__ . '/../vendor/autoload.php';

$capsule = new Capsule;

$capsule->addConnection([
    'driver'    => 'sqlite',
    'database'  => __DIR__ . '/nexussha.db',
    'prefix'    => '',
]);

// Set Eloquent globally
$capsule->setAsGlobal();
$capsule->bootEloquent();
