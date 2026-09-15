<?php

return [

    'body_max_size' => (int) env('REQUEST_BODY_MAX_SIZE', 10 * 1024 * 1024),

    'upload_max_size' => (int) env('REQUEST_UPLOAD_MAX_SIZE', 20 * 1024 * 1024),

];
