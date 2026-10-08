<?php

namespace Config;

class App
{
    /** @var array<string, class-string> */
    public static array $middlewareAliases = [
        'auth' => \App\Middleware\Authenticate::class,
        'admin' => \App\Middleware\AuthorizeAdmin::class,
    ];
}
