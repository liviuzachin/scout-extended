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

/**
 * @internal
 */
class UserDataRepository
{
    /**
     * @var \Algolia\ScoutExtended\Repositories\RemoteSettingsRepository
     */
    private $remoteRepository;

    /**
     * @var \Algolia\AlgoliaSearch\Api\SearchClient
     */
    private $client;

    /**
     * UserDataRepository constructor.
     *
     * @param \Algolia\ScoutExtended\Repositories\RemoteSettingsRepository $remoteRepository
     * @param \Algolia\AlgoliaSearch\Api\SearchClient $client
     */
    public function __construct(RemoteSettingsRepository $remoteRepository, SearchClient $client)
    {
        $this->remoteRepository = $remoteRepository;
        $this->client = $client;
    }

    /**
     * Find the User Data of the given Index.
     *
     * @param  string $indexName
     *
     * @return array
     */
    public function find(string $indexName): array
    {
        $settings = $this->remoteRepository->getSettingsRaw($indexName);

        if (array_key_exists('userData', $settings)) {
            $userData = @json_decode($settings['userData'], true);
        }

        return $userData ?? [];
    }

    /**
     * Save the User Data of the given Index.
     *
     * @param  string $indexName
     * @param  array $userData
     *
     * @return void
     */
    public function save(string $indexName, array $userData): void
    {
        $currentUserData = $this->find($indexName);

        $userDataJson = json_encode(array_merge($currentUserData, $userData));

        $response = $this->client->setSettings($indexName, ['userData' => $userDataJson]);
        $this->client->waitForTask($indexName, $response['taskID']);
    }

    /**
     * Get the settings hash.
     *
     * @param  string $indexName
     *
     * @return string
     */
    public function getSettingsHash(string $indexName): string
    {
        $userData = $this->find($indexName);

        return $userData['settingsHash'] ?? '';
    }
}
