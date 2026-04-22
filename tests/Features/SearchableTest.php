<?php

declare(strict_types=1);

namespace Tests\Features;

use Algolia\ScoutExtended\Searchable\ModelsResolver;
use App\EmptyItem;
use App\User;
use Illuminate\Support\Arr;
use Mockery;
use Tests\TestCase;

class SearchableTest extends TestCase
{
    public function testSearchable(): void
    {
        $user = factory(User::class)->create();

        $user->withScoutMetaData('_rankingInfo', []);
        $user->withScoutMetaData('_highlightResult', []);

        $metadataKeys = ModelsResolver::$metadata;

        $this->mockIndex(User::class)
            ->expects('saveObjects')
            ->with('users', Mockery::on(function ($argument) use ($metadataKeys) {
                return count(Arr::only($argument[0], $metadataKeys)) === 0;
            }))
            ->once();

        $user->searchable();
    }

    public function testSearchableWithEmptySearchableArray(): void
    {
        $item = new EmptyItem([
            'id' => 1,
            'title' => 'Example Title',
        ]);

        $item->pushSoftDeleteMetadata();

        $this->mockIndex(EmptyItem::class)
            ->expects('saveObjects')
            ->with((new EmptyItem)->searchableAs(), [])
            ->once();

        $item->searchable();
    }
}
