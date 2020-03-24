<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Pattern and storage path settings
    |--------------------------------------------------------------------------
    |
    | The env key for pattern and storage path with a default value
    |
    */
    'max_file_size' => 52428800, // size in Byte
    'pattern' => env('LOGVIEWER_PATTERN', '*.log'),
    'storage_path' => env('LOGVIEWER_STORAGE_PATH', storage_path('logs')),

    'blade' => [
        'jquery-slim' => '//cdn.staticfile.org/jquery/2.2.3/jquery.slim.min.js',
        'bootstrap' => [
            'js' => '//cdn.staticfile.org/twitter-bootstrap/4.3.1/js/bootstrap.min.js',
            'css' => '//cdn.staticfile.org/twitter-bootstrap/4.3.1/css/bootstrap.min.css',
        ],
        'dataTables' => [
            'js' => '//cdn.staticfile.org/datatables/1.10.19/js/jquery.dataTables.min.js',
            'css' => '//cdn.staticfile.org/datatables/1.10.19/css/dataTables.bootstrap4.min.css',
            'bootstrap-js' => '//cdn.staticfile.org/datatables/1.10.19/js/dataTables.bootstrap4.min.js'
        ],
        'font-awesome' => '//cdn.staticfile.org/font-awesome/5.9.0/js/all.min.js'
    ]
];
