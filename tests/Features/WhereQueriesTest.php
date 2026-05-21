<?php

declare(strict_types=1);

namespace Tests\Features;

use App\User;
use Mockery;
use Tests\TestCase;

class WhereQueriesTest extends TestCase
{
    public function testExplicitOperator(): void
    {
        $this->mockIndex(User::class)
            ->shouldReceive('searchSingleIndex')
            ->once()
            ->with('users', Mockery::subset(['query' => 'foo', 'filters' => 'views_count != 100']))
            ->andReturn(['hits' => []]);

        User::search('foo')->where('views_count', '!=', '100')->get();
    }

    public function testOmittedOperator(): void
    {
        $this->mockIndex(User::class)
            ->shouldReceive('searchSingleIndex')
            ->once()
            ->with('users', Mockery::subset(['query' => 'foo', 'filters' => 'views_count=100']))
            ->andReturn(['hits' => []]);

        User::search('foo')->where('views_count', '100')->get();
    }

    public function testWithDates(): void
    {
        $this->mockIndex(User::class)
            ->shouldReceive('searchSingleIndex')
            ->once()
            ->with('users', Mockery::subset(['query' => 'foo', 'filters' => 'views_count > '.($date = now())->getTimestamp()]))
            ->andReturn(['hits' => []]);

        User::search('foo')->where('views_count', '>', $date)->get();
    }

    public function testWhereBetween(): void
    {
        $this->mockIndex(User::class)
            ->shouldReceive('searchSingleIndex')
            ->once()
            ->with('users', Mockery::subset(['query' => 'foo', 'filters' => 'views_count: 100 TO 200']))
            ->andReturn(['hits' => []]);

        User::search('foo')->whereBetween('views_count', [100, 200])->get();
    }

    public function testWhereBetweenWithDates(): void
    {
        $date1 = now()->subMonth()->startOfMonth();
        $date2 = now()->subMonth()->endOfMonth();

        $this->mockIndex(User::class)
            ->shouldReceive('searchSingleIndex')
            ->once()
            ->with('users', Mockery::subset(['query' => 'foo', 'filters' => "created_at: {$date1->getTimestamp()} TO {$date2->getTimestamp()}"]))
            ->andReturn(['hits' => []]);

        User::search('foo')->whereBetween('created_at', [$date1, $date2])->get();
    }

    public function testWhereIn(): void
    {
        $this->mockIndex(User::class)
            ->shouldReceive('searchSingleIndex')
            ->once()
            ->with('users', Mockery::subset(['query' => 'foo', 'filters' => '(id=1 OR id=2 OR id=3 OR id=4)']))
            ->andReturn(['hits' => []]);

        User::search('foo')->whereIn('id', [1, 2, 3, 4])->get();
    }

    public function testWhereInEmptyArray(): void
    {
        $this->mockIndex(User::class)
            ->shouldReceive('searchSingleIndex')
            ->once()
            ->with('users', Mockery::subset(['query' => 'foo', 'filters' => '(0 = 1)']))
            ->andReturn(['hits' => []]);

        User::search('foo')->whereIn('id', [])->get();
    }

    public function testMultipleWheres(): void
    {
        $this->mockIndex(User::class)
            ->shouldReceive('searchSingleIndex')
            ->once()
            ->with('users', Mockery::subset(['query' => 'foo', 'filters' => '(id=1 OR id=2) AND key=value']))
            ->andReturn(['hits' => []]);

        User::search('foo')->whereIn('id', [1, 2])->where('key', 'value')->get();
    }
}
