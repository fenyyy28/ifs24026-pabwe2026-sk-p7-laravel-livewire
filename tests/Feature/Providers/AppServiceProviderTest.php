<?php

namespace Tests\Feature\Providers;

use App\Features\Stock\Contracts\StockRepositoryInterface;
use App\Features\Stock\Models\Stock;
use App\Features\Stock\Policies\StockPolicy;
use App\Features\Stock\Repositories\StockRepository;
use App\Providers\AppServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AppServiceProviderTest extends TestCase
{
    public function test_binding_repository(): void
    {
        $this->assertInstanceOf(StockRepository::class, app(StockRepositoryInterface::class));
    }

    public function test_policy_terdaftar(): void
    {
        $this->assertInstanceOf(StockPolicy::class, Gate::getPolicyFor(Stock::class));
    }

    public function test_rate_limit_1000_per_detik(): void
    {
        $limiter = RateLimiter::limiter('global');

        $guest = $limiter(Request::create('/', 'GET', [], [], [], ['REMOTE_ADDR' => '10.0.0.1']));
        $this->assertSame(1000, $guest->maxAttempts);
        $this->assertSame(1, $guest->decaySeconds);
        $this->assertSame('10.0.0.1', $guest->key);

        $request = Request::create('/');
        $request->setUserResolver(fn () => (object) ['id' => 42]);
        $this->assertSame(42, $limiter($request)->key);
    }

    public function test_force_https(): void
    {
        config(['app.force_https' => true]);
        (new AppServiceProvider($this->app))->boot();

        $this->assertStringStartsWith('https://', url('/'));

        URL::forceHttps(false);
        URL::forceScheme('http');
    }
}
