<?php

declare(strict_types=1);

/**
 * This file is part of Scout Extended.
 *
 * (c) Algolia Team <contact@algolia.com>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Algolia\ScoutExtended;

use Algolia\AlgoliaSearch\Api\AnalyticsClient;
use Algolia\AlgoliaSearch\Api\SearchClient;
use Algolia\ScoutExtended\Repositories\ApiKeysRepository;
use Illuminate\Contracts\Container\Container;
use function is_string;

class Algolia
{
    /**
     * @var \Illuminate\Contracts\Container\Container
     */
    private $container;

    /**
     * Algolia constructor.
     *
     * @param \Illuminate\Contracts\Container\Container $container
     *
     * @return void
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Get the index name for the given searchable.
     *
     * @param  string|object $searchable
     *
     * @return string
     */
    public function index($searchable): string
    {
        $searchable = is_string($searchable) ? new $searchable : $searchable;

        return $searchable->searchableAs();
    }

    /**
     * Get a client instance.
     *
     * @return \Algolia\AlgoliaSearch\Api\SearchClient
     */
    public function client(): SearchClient
    {
        return $this->container->get('algolia.client');
    }

    /**
     * Get a analytics instance.
     *
     * @return \Algolia\AlgoliaSearch\Api\AnalyticsClient
     */
    public function analytics(): AnalyticsClient
    {
        return $this->container->get('algolia.analytics');
    }

    /**
     * Get a search key for the given searchable.
     *
     * @param  string|object $searchable
     *
     * @return string
     */
    public function searchKey($searchable): string
    {
        $searchable = is_string($searchable) ? new $searchable : $searchable;

        return $this->container->make(ApiKeysRepository::class)->getSearchKey($searchable);
    }
}
