<?php

declare(strict_types=1);

namespace Tests\Features;

use App\All;
use App\EmptyItem;
use App\News;
use App\Thread;
use App\User;
use App\Wall;
use Modules\Taxonomy\Term;
use Tests\TestCase;

class FlushCommandTest extends TestCase
{
    public function testClearsIndex(): void
    {
        $client = $this->mockClient();

        foreach ([News::class, User::class, Thread::class, Wall::class, All::class, EmptyItem::class, Term::class] as $model) {
            $indexName = (new $model)->searchableAs();
            $this->mockIndex($model);
            $client->expects('clearObjects')->with($indexName)->once();
        }

        /*
         * Detects searchable models.
         */
        $this->artisan('scout:flush');
    }
}
