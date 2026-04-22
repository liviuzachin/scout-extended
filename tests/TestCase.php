<?php

declare(strict_types=1);

namespace Tests;

use Algolia\AlgoliaSearch\Api\SearchClient;
use Algolia\ScoutExtended\Engines\AlgoliaEngine;
use Algolia\ScoutExtended\Facades\Algolia as AlgoliaFacade;
use Algolia\ScoutExtended\Managers\EngineManager;
use Algolia\ScoutExtended\ScoutExtendedServiceProvider;
use Algolia\ScoutExtended\Settings\Compiler;
use Illuminate\Support\Facades\Artisan;
use Laravel\Scout\ScoutServiceProvider;
use Mockery;
use Mockery\MockInterface;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->app->setBasePath(__DIR__.'/laravel');

        $this->withFactories(database_path('factories'));
        Artisan::call('migrate:fresh', ['--database' => 'testbench']);
        @unlink(config_path('scout-users.php'));
    }

    public function tearDown(): void
    {
        @unlink(__DIR__.'/laravel/config/scout-users.php');

        $this->addToAssertionCount(Mockery::getContainer()->mockery_getExpectationCount());

        Mockery::close();

        parent::tearDown();
    }

    protected function getPackageProviders($app)
    {
        return [
            ScoutServiceProvider::class,
            ScoutExtendedServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app)
    {
        return [
            'Algolia' => AlgoliaFacade::class,
        ];
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('scout.algolia.id', 'test-app-id');
        $app['config']->set('scout.algolia.secret', 'test-api-key');
    }

    protected function defaults(): array
    {
        $clientMock = $this->mockIndex('temp-laravel-scout-extended', $defaults = require __DIR__.'/resources/defaults.php');
        $clientMock->shouldReceive('deleteIndex')->with('temp-laravel-scout-extended')->zeroOrMoreTimes();

        return $defaults;
    }

    protected function assertLocalHas(array $settings, ?string $settingsPath = null): void
    {
        if ($settingsPath === null) {
            $settingsPath = config_path('scout-users.php');
        }

        $this->assertFileExists($settingsPath);
        $this->assertEquals($settings, require $settingsPath);
    }

    protected function local(): array
    {
        $viewVariables = array_fill_keys(Compiler::getViewVariables(), null);

        return array_merge($viewVariables, [
            'searchableAttributes' => [
                'name',
                'email',
                'category_type',
            ],
            'customRanking' => [
                'desc(email_verified_at)',
                'desc(created_at)',
                'desc(updated_at)',
                'desc(views_count)',
            ],
            'attributesForFaceting' => ['category_type'],
            'queryLanguages' => ['en'],
        ]);
    }

    protected function localMd5(): string
    {
        $content = $this->local();

        ksort($content);

        return md5(serialize($content));
    }

    protected function mockEngine(): MockInterface
    {
        $engineMock = mock(new AlgoliaEngine($this->mockClient()))->makePartial()->shouldIgnoreMissing();

        $managerMock = mock(EngineManager::class)->makePartial()->shouldIgnoreMissing();

        $managerMock->shouldReceive('driver')->andReturn($engineMock);

        $this->swap(EngineManager::class, $managerMock);

        return $engineMock;
    }

    protected function mockClient(): MockInterface
    {
        try {
            $client = $this->app->get(SearchClient::class);
            if ($client instanceof MockInterface) {
                return $client;
            }
        } catch (\Exception $e) {
            // Real factory threw — create fresh mock
        }

        $clientMock = mock(SearchClient::class)->shouldIgnoreMissing();

        $this->swap(SearchClient::class, $clientMock);

        return $clientMock;
    }

    protected function mockIndex(string $model, array $settings = [], ?array $userData = null): MockInterface
    {
        $indexName = class_exists($model) ? (new $model)->searchableAs() : $model;

        $clientMock = $this->mockClient();

        $clientMock->shouldReceive('getSettings')
            ->with($indexName)
            ->zeroOrMoreTimes()
            ->andReturn(array_merge($settings, [
                'userData' => @json_encode($userData),
            ]));

        $algoliaEngine = new AlgoliaEngine($clientMock);

        $managerMock = mock(EngineManager::class)->makePartial();

        $managerMock->shouldReceive('driver')->andReturn($algoliaEngine);

        $this->swap(EngineManager::class, $managerMock);

        return $clientMock;
    }

    protected function assertSettingsSet(string $indexName, array $settings, ?array $userData = null): void
    {
        $clientMock = $this->mockClient();

        if (! empty($settings)) {
            $clientMock->shouldReceive('setSettings')->once()
                ->with($indexName, $settings)
                ->andReturn($this->mockResponse());
        }

        if (! empty($userData)) {
            $clientMock->shouldReceive('setSettings')->once()
                ->with($indexName, ['userData' => @json_encode($userData)])
                ->andReturn($this->mockResponse());
        }
    }

    protected function mockResponse(): array
    {
        return ['taskID' => 1];
    }
}
