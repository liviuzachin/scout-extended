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

namespace Algolia\ScoutExtended\Contracts;

use Algolia\ScoutExtended\Settings\Settings;

interface LocalSettingsRepositoryContract
{
    /**
     * Checks if the given index settings exists.
     *
     * @param  string $indexName
     *
     * @return bool
     */
    public function exists(string $indexName): bool;

    /**
     * Get the settings path of the given index name.
     *
     * @param  string $indexName
     *
     * @return string
     */
    public function getPath(string $indexName): string;

    /**
     * Find the settings of the given Index.
     *
     * @param string $indexName
     *
     * @return \Algolia\ScoutExtended\Settings\Settings
     */
    public function find(string $indexName): Settings;
}
