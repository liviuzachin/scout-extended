<?php

declare(strict_types=1);

namespace Tests\Features;

use Algolia\AlgoliaSearch\Api\AnalyticsClient;
use Algolia\AlgoliaSearch\Api\SearchClient;
use Algolia\ScoutExtended\Algolia;
use App\User;
use Tests\TestCase;

class AlgoliaTest extends TestCase
{
    public $algolia;

    public function setUp(): void
    {
        parent::setUp();

        $this->algolia = resolve(Algolia::class);
    }

    public function testIndexGetter(): void
    {
        $indexName = $this->algolia->index(User::class);
        $this->assertIsString($indexName);

        $model = new User;
        $indexName = $this->algolia->index($model);
        $this->assertIsString($indexName);
        $this->assertSame($model->searchableAs(), $indexName);
    }

    public function testClientGetter(): void
    {
        $this->assertInstanceOf(SearchClient::class, $this->algolia->client());
    }

    public function testAnalyticsGetter(): void
    {
        $this->assertInstanceOf(AnalyticsClient::class, $this->algolia->analytics());
    }
}
