<?php

namespace Core\Constants;

class Constants
{
    public static function rootPath(): StringPath
    {
        return new StringPath(dirname(dirname(__DIR__)));
    }

    public static function databasePath(): StringPath
    {
        return self::rootPath()->join('/database');
    }

    /**
     * Diretório físico (filesystem) onde os uploads são gravados.
     * Ex: /var/www/public/assets/uploads
     */
    public static function uploadsPath(): StringPath
    {
        return self::rootPath()->join('public')->join(self::uploadsUrl());
    }

    /**
     * Caminho público (URL) pelo qual o Nginx serve os uploads.
     * Em ambiente de testes usa uma subpasta isolada para não misturar
     * os arquivos gerados pelos testes com os arquivos de desenvolvimento.
     */
    public static function uploadsUrl(): string
    {
        $url = '/assets/uploads';

        if (($_ENV['APP_ENV'] ?? getenv('APP_ENV')) === 'testing') {
            $url .= '/test';
        }

        return $url;
    }
}
