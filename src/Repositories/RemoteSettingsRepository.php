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

namespace Algolia\ScoutExtended\Repositories;

use Algolia\AlgoliaSearch\Api\SearchClient;
use Algolia\AlgoliaSearch\Exceptions\NotFoundException;
use Algolia\ScoutExtended\Settings\Settings;

/**
 * @internal
 */
class RemoteSettingsRepository
{
    /**
     * Settings that may be know by other names.
     *
     * @var array
     */
    private static $aliases = [
        'attributesToIndex' => 'searchableAttributes',
    ];

    /**
     * @var \Algolia\AlgoliaSearch\Api\SearchClient
     */
    private $client;

    /**
     * @var array
     */
    private $defaults;

    /**
     * RemoteRepository constructor.
     *
     * @param \Algolia\AlgoliaSearch\Api\SearchClient $client
     *
     * @return void
     */
    public function __construct(SearchClient $client)
    {
        $this->client = $client;
    }

    /**
     * Get the default settings.
     *
     * @return array
     */
    public function defaults(): array
    {
        if ($this->defaults === null) {
            $indexName = 'temp-laravel-scout-extended';
            $this->defaults = $this->getSettingsRaw($indexName);
            $this->client->deleteIndex($indexName);
        }

        return $this->defaults;
    }

    /**
     * Find the settings of the given Index.
     *
     * @param  string $indexName
     *
     * @return \Algolia\ScoutExtended\Settings\Settings
     */
    public function find(string $indexName): Settings
    {
        return new Settings($this->getSettingsRaw($indexName), $this->defaults());
    }

    /**
     * @param string $indexName
     * @param \Algolia\ScoutExtended\Settings\Settings $settings
     *
     * @return void
     */
    public function save(string $indexName, Settings $settings): void
    {
        $response = $this->client->setSettings($indexName, $settings->compiled());
        $this->client->waitForTask($indexName, $response['taskID']);
    }

    /**
     * @param  string $indexName
     *
     * @return array
     */
    public function getSettingsRaw(string $indexName): array
    {
        try {
            $rawSettings = $this->client->getSettings($indexName) ?? [];
        } catch (NotFoundException $e) {
            $response = $this->client->setSettings($indexName, []);
            $this->client->waitForTask($indexName, $response['taskID']);
            $rawSettings = $this->client->getSettings($indexName) ?? [];
        }

        foreach (self::$aliases as $from => $to) {
            if (array_key_exists($from, $rawSettings)) {
                $rawSettings[$to] = $rawSettings[$from];
                unset($rawSettings[$from]);
            }
        }

        return $rawSettings;
    }
}
