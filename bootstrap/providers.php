<?php

use App\Providers\AppServiceProvider;
use App\Providers\NativeServiceProvider;
use App\Providers\RepositoryServiceProvider;

return [
    AppServiceProvider::class,
    NativeServiceProvider::class,
    RepositoryServiceProvider::class,
];
