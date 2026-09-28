<?php

namespace App\Providers;

use App\Models\Cellphone;
use App\Models\AuditLog;
use App\Models\HardwareAsset;
use App\Models\Peripheral;
use App\Models\Printer;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Cache;
use App\Services\InventoryDataset;

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
        Relation::enforceMorphMap([
            'cellphone' => Cellphone::class,
            'inventory' => HardwareAsset::class,
            'peripheral' => Peripheral::class,
            'printer' => Printer::class,
            'user' => User::class,
        ]);

        View::composer('layouts.app', function ($view) {
            $view->with('liveCursor', (int) Cache::remember('inventory.live_cursor', 2, fn () => AuditLog::max('id') ?? 0));
            // Apache/XAMPP puede cortar streams SSE; el sondeo corto es mas estable en LAN.
            $view->with('liveUsePolling', filter_var(env('INVENTORY_LIVE_POLLING', true), FILTER_VALIDATE_BOOLEAN));
            $view->with('inventoryDataset', app(InventoryDataset::class)->forRequest(request()));
        });
    }
}
