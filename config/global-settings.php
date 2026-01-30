<?php

declare(strict_types=1);

return [
    'admin' => [
        'enabled' => true,
        'prefix' => 'admin/settings',
        'middleware' => ['web', 'auth'],
    ],
];
