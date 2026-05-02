<?php

return [
    'app_url' => env('APP_URL'),
    'asset_url' => env('ASSET_URL'),

    'middleware' => ['web'],

    'component_layout' => 'components.layouts.app',

    'view_path' => resource_path('views/livewire'),

    'component_locations' => [
        resource_path('views/components'),
        resource_path('views/livewire'),
    ],

    'component_namespaces' => [
        'layouts' => resource_path('views/components/layouts'),
        'pages' => resource_path('views/pages'),
    ],

    'livewire_class_name' => env('LIVEWIRE_CLASS_NAME', 'PascalCase'),

    'url_asset_prefix' => env('LIVEWIRE_URL_ASSET_PREFIX'),

    'js_endpoint' => env('LIVEWIRE_JS_ENDPOINT'),

    'js_namespace' => null,

    'emit_theme' => false,

    'manifest_path' => null,
];