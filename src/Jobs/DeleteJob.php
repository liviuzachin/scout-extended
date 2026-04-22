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

namespace Algolia\ScoutExtended\Jobs;

use Algolia\AlgoliaSearch\Api\SearchClient;
use Algolia\ScoutExtended\Searchable\ObjectIdEncrypter;
use Illuminate\Support\Collection;

/**
 * @internal
 */
class DeleteJob
{
    /**
     * @var \Illuminate\Support\Collection
     */
    private $searchables;

    /**
     * DeleteJob constructor.
     *
     * @param \Illuminate\Support\Collection $searchables
     *
     * @return void
     */
    public function __construct(Collection $searchables)
    {
        $this->searchables = $searchables;
    }

    /**
     * @param \Algolia\AlgoliaSearch\Api\SearchClient $client
     *
     * @return void
     */
    public function handle(SearchClient $client): void
    {
        if ($this->searchables->isEmpty()) {
            return;
        }

        // Checking whether or not to use the deprecated `deleteBy` method
        // here instead of creating an second job for it so that any existing
        // integrations will still function even if they dispatch jobs manually
        // or extend this class.

        if (config('scout.algolia.use_deprecated_delete_by', false)) {
            $this->handleDeprecatedDeleteBy($client);
        } else {
            $this->handleDeleteObjects($client);
        }
    }

    /**
     * Handle deleting objects.
     *
     * @param \Algolia\AlgoliaSearch\Api\SearchClient $client
     * @return void
     */
    protected function handleDeleteObjects(SearchClient $client)
    {
        $indexName = $this->searchables->first()->searchableAs();

        // First fetch all object IDs by tags.
        $objects = $client->browseObjects($indexName, [
            'attributesToRetrieve' => [
                'objectID',
            ],
            'tagFilters' => [
                $this->searchables->map(function ($searchable) {
                    return ObjectIdEncrypter::encrypt($searchable);
                })->toArray(),
            ],
        ]);

        // The ObjectIterator will fetch all pages for us automatically.
        $objectIds = [];
        foreach ($objects as $object) {
            if (isset($object['objectID'])) {
                $objectIds[] = $object['objectID'];
            }
        }

        // Then delete the objects using their object IDs.
        $response = $client->deleteObjects($indexName, $objectIds);

        if (config('scout.synchronous', false)) {
            $client->waitForTask($indexName, $response['taskID']);
        }
    }

    /**
     * Handle deleting objects using the deprecated `deleteBy` method.
     *
     * @deprecated Use handleDeleteObjects() instead. Enable via scout.algolia.use_deprecated_delete_by for backwards compatibility only.
     *
     * @param \Algolia\AlgoliaSearch\Api\SearchClient $client
     * @return void
     */
    protected function handleDeprecatedDeleteBy(SearchClient $client)
    {
        $indexName = $this->searchables->first()->searchableAs();

        $response = $client->deleteBy($indexName, [
            'tagFilters' => [
                $this->searchables->map(function ($searchable) {
                    return ObjectIdEncrypter::encrypt($searchable);
                })->toArray(),
            ],
        ]);

        if (config('scout.synchronous', false)) {
            $client->waitForTask($indexName, $response['taskID']);
        }
    }
}
