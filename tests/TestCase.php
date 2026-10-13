<?php

namespace Saade\Facehash\Tests;

use Illuminate\Encryption\Encrypter;
use Orchestra\Testbench\TestCase as Orchestra;
use Saade\Facehash\FacehashServiceProvider;

abstract class TestCase extends Orchestra
{
    /** @var array<string, mixed> */
    protected array $packageConfig = [];

    protected function getPackageProviders($app): array
    {
        return [FacehashServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:' . base64_encode(Encrypter::generateKey('AES-256-CBC')));

        foreach ($this->packageConfig as $key => $value) {
            $app['config']->set("facehash.{$key}", $value);
        }
    }

    /**
     * The provider reads its config while booting, so options like
     * `route.enabled` only take effect on an application booted with them.
     *
     * @param  array<string, mixed>  $config  dot-notation keys under `facehash.`
     */
    public function bootWithConfig(array $config): static
    {
        $this->packageConfig = $config;
        $this->reloadApplication();

        return $this;
    }
}
