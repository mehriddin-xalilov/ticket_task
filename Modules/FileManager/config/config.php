<?php

return [
    'name' => 'FileManager',
    'allow_extensions' => 'jpg,jpeg,png,gif,xlx,xlsx,doc,docx,pdf,txt,csv,mp4,mp3,webp',
    'domain' => env('DOMAIN'),
    'static_url' => env('CDN_STATIC_URL'),
    'thumbs' => [
        'small' => [
            'w' => 320,
            'h' => 240,
            'q' => 70,
            'slug' => 'small'
        ],
        'low' => [
            'w' => 640,
            'h' => 480,
            'q' => 70,
            'slug' => 'low'
        ],
        'normal' => [
            'w' => 1024,
            'h' => 728,
            'q' => 100,
            'slug' => 'normal'
        ],
        'original' => [
            'w' => 1048,
            'h' => 728,
            'q' => 100,
            'slug' => 'original'
        ]
    ],
    'images_ext' => [
        'jpg',
        'png',
        'gif',
        'bmp',
        'webp',
    ]
];
