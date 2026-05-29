<?php

return [
    // Set true to temporarily block outbound AUTOMATED communications on all channels.
    // Manual sends remain unaffected.
    'pause_automated_all_channels' => (bool) env('PAUSE_AUTOMATED_COMMUNICATIONS', false),
];
