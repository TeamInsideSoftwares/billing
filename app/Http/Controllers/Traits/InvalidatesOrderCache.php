<?php

namespace App\Http\Controllers\Traits;

use App\Models\Product;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

trait InvalidatesOrderCache
{
    private function invalidateOrderCache(): void
    {
        /*
        $key = (string) config('services.cache_invalidation.key', '');

        if ($key === '') {
            return;
        }

        $urls = Product::where('status', 'active')
            ->pluck('url')
            ->toArray();

        foreach ($urls as $url) {
            try {
                Http::acceptJson()
                    ->timeout(5)
                    ->connectTimeout(3)
                    ->withHeaders(['X-API-KEY' => $key])
                    ->post(rtrim($url, '/').'/api/cache/invalidate-orders');
            } catch (\Exception $e) {
                Log::warning('Failed to invalidate order cache.', [
                    'url' => $url,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        */
    }
}
