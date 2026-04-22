<?php

declare(strict_types=1);

namespace Tests\Features;

use App\All;
use App\EmptyItem;
use App\News;
use App\Thread;
use App\User;
use App\Wall;
use function count;
use Illuminate\Support\Facades\Artisan;
use Mockery;
use Modules\Taxonomy\Term;
use Tests\TestCase;

class ImportCommandTest extends TestCase
{
    public function testImport(): void
    {
        Wall::bootSearchable();
        All::bootSearchable();
        News::bootSearchable();

        $this->app['config']->set('scout.soft_delete', true);

        factory(User::class, 5)->create();
        factory(EmptyItem::class, 2)->create();

        $client = $this->mockClient();

        // Detects searchable models.
        $this->mockIndex(User::class);
        $client->expects('clearObjects')->with('users')->once();
        $client->expects('saveObjects')->with('users', Mockery::on(function ($argument) {
            return count($argument) === 5 && $argument[0]['objectID'] === 'App\User::1';
        }))->once();

        // Detects aggregators.
        $this->mockIndex(Wall::class);
        $client->expects('clearObjects')->with('wall')->once();
        $client->expects('saveObjects')->with('wall', Mockery::on(function ($argument) {
            return count($argument) === 5 && $argument[0]['objectID'] === 'App\User::1';
        }))->once();

        $this->mockIndex(All::class);
        $client->expects('clearObjects')->with('all')->once();
        $client->expects('saveObjects')->with('all', Mockery::on(function ($argument) {
            return count($argument) === 5 && $argument[0]['objectID'] === 'App\User::1';
        }))->once();

        $this->mockIndex(News::class);
        $client->expects('clearObjects')->with('news')->once();
        $client->expects('saveObjects')->with('news', Mockery::on(function ($argument) {
            return count($argument) === 5 && $argument[0]['objectID'] === 'App\User::1';
        }))->once();

        // Detects searchable models.
        $this->mockIndex(Thread::class);
        $client->expects('clearObjects')->with('threads')->once();

        // Detects searchable models.
        $emptyItemIndex = (new EmptyItem)->searchableAs();
        $this->mockIndex(EmptyItem::class);
        $client->expects('clearObjects')->with($emptyItemIndex)->once();
        $client->expects('saveObjects')->with($emptyItemIndex, [])->once();

        $this->mockIndex(Term::class);
        $client->expects('clearObjects')->with('terms')->once();

        Artisan::call('scout:import');
    }
}
