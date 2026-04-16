<?php

namespace Beon\Laravel\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Beon\Laravel\BeonServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [BeonServiceProvider::class];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'Beon' => \Beon\Laravel\Facades\Beon::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('beon.api_key',        'test-key');
        $app['config']->set('beon.base_url',        'https://v3.api.beon.chat');
        $app['config']->set('beon.timeout',         30);
        $app['config']->set('beon.webhook_secret',  'test-secret');
        $app['config']->set('beon.default_channel_id', 1);
    }

}
