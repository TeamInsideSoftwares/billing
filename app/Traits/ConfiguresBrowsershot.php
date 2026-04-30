<?php

namespace App\Traits;

use Spatie\Browsershot\Browsershot;

trait ConfiguresBrowsershot
{
    /**
     * Get a pre-configured Browsershot instance.
     *
     * @param string $html
     * @return Browsershot
     */
    protected function getBrowsershot(string $html): Browsershot
    {
        return Browsershot::html($html)
            ->setNodeBinary(config('browsershot.node_binary'))
            ->setNpmBinary(config('browsershot.npm_binary'))
            ->setChromePath(config('browsershot.chrome_path'))
            ->noSandbox()
            ->format('A4')
            ->margins(5, 5, 5, 5)
            ->showBackground();
    }
}
