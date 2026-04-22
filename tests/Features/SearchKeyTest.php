<?php

declare(strict_types=1);

namespace Tests\Features;

use Algolia\ScoutExtended\Facades\Algolia;
use Mockery;
use App\User;
use App\Wall;
use Tests\TestCase;

class SearchKeyTest extends TestCase
{
    public function testWhenSearchApiDontExists(): void
    {
        $this->mockClient()->shouldReceive('listApiKeys')->andReturn(['keys' => []]);

        $this->mockClient()->shouldReceive('addApiKey')->with([
            'acl' => ['search'],
            'description' => config('app.name').'::searchKey',
        ])->andReturn(['key' => 'bar']);

        $this->mockClient()->shouldReceive('generateSecuredApiKey')->with('bar', Mockery::subset([
            'restrictIndices' => 'users',
        ]))->andReturn('barSecured');

        $this->assertSame(Algolia::searchKey(User::class), 'barSecured');
    }

    public function testWhenSearchApiDontExistsAndInvalidKeysExist(): void
    {
        $this->mockClient()->shouldReceive('listApiKeys')->andReturn(['keys' => [['foo' => 'bar']]]);

        $this->mockClient()->shouldReceive('addApiKey')->with([
            'acl' => ['search'],
            'description' => config('app.name').'::searchKey',
        ])->andReturn(['key' => 'bar']);

        $this->mockClient()->shouldReceive('generateSecuredApiKey')->with('bar', Mockery::subset([
            'restrictIndices' => 'users',
        ]))->andReturn('barSecured');

        $this->assertSame(Algolia::searchKey(User::class), 'barSecured');
    }

    public function testWhenSearchApiAlreadyExists(): void
    {
        $this->mockClient()->shouldReceive('listApiKeys')->andReturn(['keys' => [
            [
                'description' => config('app.name').'::searchKey',
                'value' => 'bar',
            ],
        ]]);

        $this->mockClient()->shouldReceive('generateSecuredApiKey')->with('bar', Mockery::subset([
            'restrictIndices' => 'wall',
        ]))->andReturn('barSecured');

        $this->assertSame(Algolia::searchKey(new Wall()), 'barSecured');
    }
}
