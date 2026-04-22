<?php

declare(strict_types=1);

namespace Tests\Features;

use App\User;
use Illuminate\Support\Facades\Artisan;
use Mockery;
use Tests\TestCase;

class StatusCommandTest extends TestCase
{
    public function testStatus(): void
    {
        $this->mockIndex(User::class, $this->defaults())
            ->shouldReceive('searchSingleIndex')
            ->with('users', Mockery::any())
            ->andReturn(['hits' => [], 'nbHits' => 0]);

        Artisan::call('scout:status', ['searchable' => User::class]);
    }
}
