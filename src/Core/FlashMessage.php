<?php

declare(strict_types=1);

namespace App\Core;

/**
 * FlashMessage - Sistema de Mensagens de Sessao Unica.
 *
 * Mensagens flash existem por apenas UMA requisicao.
 * Sao usadas para feedback apos redirecionamentos (ex: erro de login,
 * sucesso no cadastro, acesso negado).
 *
 * Referenciado na rubrica como: lib/FlashMessage.php
 * (Este arquivo esta em src/Core/ seguindo a estrutura PSR-4 do projeto)
 */
class FlashMessage
{
    private const SESSION_KEY = '_flash';

    /**
     * Define uma mensagem flash para a proxima requisicao.
     *
     * @param string $type  Tipo da mensagem: 'success', 'error', 'warning', 'info'
     * @param string $message Texto da mensagem
     */
    public static function set(string $type, string $message): void
    {
        Session::start();
        /** @var array<string, string> $flash */
        $flash = Session::get(self::SESSION_KEY, []);
        $flash[$type] = $message;
        Session::set(self::SESSION_KEY, $flash);
    }

    /**
     * Recupera e REMOVE a mensagem flash do tipo especificado.
     * Apos ser lida, a mensagem e destruida (nao aparece novamente).
     */
    public static function get(string $type): ?string
    {
        Session::start();
        /** @var array<string, string> $flash */
        $flash = Session::get(self::SESSION_KEY, []);

        if (!isset($flash[$type])) {
            return null;
        }

        $message = $flash[$type];
        unset($flash[$type]);
        Session::set(self::SESSION_KEY, $flash);

        return $message;
    }

    /**
     * Verifica se existe uma mensagem flash do tipo especificado.
     */
    public static function has(string $type): bool
    {
        /** @var array<string, string> $flash */
        $flash = Session::get(self::SESSION_KEY, []);
        return isset($flash[$type]);
    }

    /**
     * Retorna TODAS as mensagens flash e as remove da sessao.
     *
     * @return array<string, string>
     */
    public static function all(): array
    {
        /** @var array<string, string> $flash */
        $flash = Session::get(self::SESSION_KEY, []);
        Session::remove(self::SESSION_KEY);
        return $flash;
    }
}
