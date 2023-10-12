<?php

namespace Oneduo\RecaptchaEnterprise\Tests;

use Illuminate\Support\ServiceProvider;

class DummyServiceProvider extends ServiceProvider
{

    public function boot(): void
    {
        $this->app['translator']->addNamespace('recaptcha-enterprise', __DIR__ . '../resorces/lang');
    }

}