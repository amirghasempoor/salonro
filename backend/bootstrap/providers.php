<?php

use App\Providers\AppServiceProvider;
use App\Providers\DataTableServiceProvider;
use App\Providers\FileServiceProvider;
use App\Providers\OtpServiceProvider;
use App\Providers\SmsServiceProvider;
use Expert\Manager\HallService\HallServiceServiceProvider;
use Expert\Profile\ProfileServiceProvider;

return [
    AppServiceProvider::class,
    DataTableServiceProvider::class,
    OtpServiceProvider::class,
    FileServiceProvider::class,
    SmsServiceProvider::class,
    ProfileServiceProvider::class,
    HallServiceServiceProvider::class,
];
