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

namespace Algolia\ScoutExtended\Engines;

use Algolia\AlgoliaSearch\Api\SearchClient;
use Algolia\ScoutExtended\Jobs\DeleteJob;
use Algolia\ScoutExtended\Jobs\UpdateJob;
use Algolia\ScoutExtended\Searchable\ModelsResolver;
use Algolia\ScoutExtended\Searchable\ObjectIdEncrypter;
use Illuminate\Support\LazyCollection;
use Illuminate\Support\Str;
use Laravel\Scout\Builder;
use Laravel\Scout\Engines\Algolia4Engine;
use function is_array;

class AlgoliaEngine extends Algolia4Engine
{
    /**
     * @param \Algolia\AlgoliaSearch\Api\SearchClient $algolia
     *
     * @return void
     */
    public function setClient($algolia): void
    {
        $this->algolia = $algolia;
    }

    /**
     * Get the client.
     *
     * @return \Algolia\AlgoliaSearch\Api\SearchClient
     */
    public function getClient(): SearchClient
    {
        return $this->algolia;
    }

    /**
     * {@inheritdoc}
     */
    public function update($searchables)
    {
        dispatch_sync(new UpdateJob($searchables));
    }

    /**
     * {@inheritdoc}
     */
    public function delete($searchables)
    {
        dispatch_sync(new DeleteJob($searchables));
    }

    /**
     * {@inheritdoc}
     */
    public function search(Builder $builder)
    {
        return $this->performSearch($builder, array_filter([
            'numericFilters' => $this->filters($builder),
            'hitsPerPage' => $builder->limit,
        ]));
    }

    /**
     * {@inheritdoc}
     */
    public function paginate(Builder $builder, $perPage, $page)
    {
        return $this->performSearch($builder, [
            'numericFilters' => $this->filters($builder),
            'hitsPerPage' => $perPage,
            'page' => $page - 1,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function map(Builder $builder, $results, $searchable)
    {
        if (count($results['hits']) === 0) {
            return $searchable->newCollection();
        }

        return app(ModelsResolver::class)->from($builder, $searchable, $results);
    }

    /**
     * {@inheritdoc}
     */
    public function lazyMap(Builder $builder, $results, $searchable)
    {
        return LazyCollection::make($this->map($builder, $results, $searchable));
    }

    /**
     * @return array
     */
    protected function filters(Builder $builder)
    {
        $operators = ['<', '<=', '=', '!=', '>=', '>', ':'];

        return collect($builder->wheres)->map(function ($value, $key) use ($operators) {
            if (! is_array($value)) {
                if (Str::endsWith($key, $operators) || Str::startsWith($value, $operators)) {
                    return $key.' '.$value;
                }

                return $key.'='.$value;
            }

            return $value;
        })->values()->all();
    }

    /**
     * Pluck and return the primary keys of the given results.
     *
     * @param  mixed  $results
     * @return \Illuminate\Support\Collection
     */
    public function mapIds($results)
    {
        return collect($results['hits'])->pluck('objectID')->values()
            ->map([ObjectIdEncrypter::class, 'decryptSearchableKey']);
    }
}
