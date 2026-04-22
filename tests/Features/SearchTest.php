<?php

declare(strict_types=1);

namespace Tests\Features;

use Algolia\ScoutExtended\Exceptions\ShouldReimportSearchableException;
use App\Thread;
use App\User;
use Mockery;
use Tests\TestCase;

class SearchTest extends TestCase
{
    public function testSearchEmpty(): void
    {
        $this->mockIndex(Thread::class)
            ->shouldReceive('searchSingleIndex')
            ->with('threads', Mockery::any())
            ->once()
            ->andReturn(['hits' => []]);

        $models = Thread::search('input')->get();

        $this->assertCount(0, $models);
    }

    public function testSearchOrder(): void
    {
        $client = $this->mockIndex(Thread::class);

        $client->shouldReceive('saveObjects')->with('threads', Mockery::any())->times(3);

        $client->shouldReceive('searchSingleIndex')
            ->with('threads', Mockery::any())
            ->once()
            ->andReturn([
                'hits' => [
                    ['objectID' => 'App\Thread::3'],
                    ['objectID' => 'App\Thread::1'],
                    ['objectID' => 'App\Thread::2'],
                ],
            ]);

        $threads = factory(Thread::class, 3)->create();

        $models = Thread::search('input')->get();

        $this->assertCount(3, $models);

        $this->assertInstanceOf(Thread::class, $models->get(0));
        $this->assertSame($threads[2]->subject, $models->get(0)->subject);
        $this->assertSame($threads[2]->id, $models->get(0)->id);

        $this->assertInstanceOf(Thread::class, $models->get(1));
        $this->assertSame($threads[0]->subject, $models->get(1)->subject);
        $this->assertSame($threads[0]->id, $models->get(1)->id);

        $this->assertInstanceOf(Thread::class, $models->get(2));
        $this->assertSame($threads[1]->subject, $models->get(2)->subject);
        $this->assertSame($threads[1]->id, $models->get(2)->id);
    }

    public function testInvalidObjectId(): void
    {
        $this->expectException(ShouldReimportSearchableException::class);

        $this->mockIndex(Thread::class)
            ->shouldReceive('searchSingleIndex')
            ->with('threads', Mockery::any())
            ->once()
            ->andReturn(['hits' => [['objectID' => '1']]]);

        Thread::search('input')->get();
    }

    public function testSearchContainsMetadata(): void
    {
        $client = $this->mockIndex(User::class);

        $client->expects('saveObjects')->with('users', Mockery::any())->once();

        $client->shouldReceive('searchSingleIndex')
            ->with('users', Mockery::any())
            ->once()
            ->andReturn([
                'hits' => [
                    [
                        'objectID' => 'App\User::1',
                        '_highlightResult' => [],
                        '_rankingInfo' => [],
                    ],
                ],
            ]);

        factory(User::class)->create();

        $scoutMetaData = User::search('')->get()->first()->scoutMetaData();

        $this->assertArrayHasKey('_highlightResult', $scoutMetaData);
        $this->assertArrayHasKey('_rankingInfo', $scoutMetaData);
    }
}
