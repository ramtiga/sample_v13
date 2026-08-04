<?php

use App\Providers\AppServiceProvider;
use LaravelDoctrine\ORM\Validation\PresenceVerifierProvider;

return [
    AppServiceProvider::class,
    PresenceVerifierProvider::class,
];
