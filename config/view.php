<?php

return [
    /*
    |--------------------------------------------------------------------------
    | View Storage Paths
    |--------------------------------------------------------------------------
    |
    | Most templating systems load templates from disk. Here you may specify
    | an array of paths that should be checked for your views. Of course
    | the usual Laravel view path has already been registered for you.
    |
    */

    'paths' => [
        resource_path('Views'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Compiled View Path
    |--------------------------------------------------------------------------
    |
    | This option determines where all the compiled Blade templates will be
    | stored for your application. Typically, this is within the storage
    | directory. However, as usual, you are free to change this value.
    |
    */

    'compiled' => env('VIEW_COMPILED_PATH', realpath(storage_path('framework/views'))),

    /*
    |--------------------------------------------------------------------------
    | View Namespaces
    |--------------------------------------------------------------------------
    |
    | You may want to register additional namespaces to the view loader so
    | that templates may be loaded from other paths or shared packages.
    | Here, you can map namespace names to one or more filesystem paths.
    |
    */

    'namespaces' => [
        // 'Example' => resource_path('Views/Example'),
    ],
];
