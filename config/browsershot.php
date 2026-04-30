<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Node Binary Path
    |--------------------------------------------------------------------------
    |
    | The path to the node binary on your system.
    |
    */
    'node_binary' => env('BROWSERSHOT_NODE_BINARY', '/home/alpha/.nvm/versions/node/v24.15.0/bin/node'),

    /*
    |--------------------------------------------------------------------------
    | NPM Binary Path
    |--------------------------------------------------------------------------
    |
    | The path to the npm binary on your system.
    |
    */
    'npm_binary' => env('BROWSERSHOT_NPM_BINARY', '/home/alpha/.nvm/versions/node/v24.15.0/bin/npm'),

    /*
    |--------------------------------------------------------------------------
    | Chrome Binary Path
    |--------------------------------------------------------------------------
    |
    | The path to the chrome/chromium binary on your system.
    |
    */
    'chrome_path' => env('BROWSERSHOT_CHROME_PATH', '/usr/bin/google-chrome-stable'),
];
