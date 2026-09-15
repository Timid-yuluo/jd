<?php

declare(strict_types=1);

return [
    'sample_rate' => (float) env('TRACKING_SAMPLE_RATE', 1.0),

    'retention_days' => (int) env('TRACKING_RETENTION_DAYS', 90),
];
