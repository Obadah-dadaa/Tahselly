<?php

namespace App\Providers;

use App\Support\Edition;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Behind a hosting proxy the request may look like http; production links must always be https,
        // and always on APP_URL's domain so a forged Host header can't put another site into emailed links
        if ($this->app->isProduction()) {
            URL::forceRootUrl(config('app.url'));
            URL::forceScheme('https');
        }

        // @beta ... @else ... @endbeta: the beta site shows no prices, plans or payment
        Blade::if('beta', fn () => Edition::isBeta());

        Blade::directive('money', fn (string $expression) => "<?php echo number_format((float) ({$expression}), 2); ?>");
        Blade::directive('short', fn (string $expression) => "<?php echo \App\Providers\AppServiceProvider::shortMoney((float) ({$expression})); ?>");
    }

    /**
     * Compact amounts for KPI tiles: 1,250 / 48.2K / 1.3M.
     */
    public static function shortMoney(float $value): string
    {
        return match (true) {
            abs($value) >= 1_000_000 => round($value / 1_000_000, 1).'M',
            abs($value) >= 10_000 => round($value / 1_000, 1).'K',
            default => number_format($value, 0),
        };
    }
}
