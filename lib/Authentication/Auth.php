<?php

namespace Lib\Authentication;

use App\Models\User;

class Auth
{
    /**
     * @param User $user
     */
    public static function login($user): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }

        $_SESSION['user']['id'] = $user->id;
    }

    public static function user(): ?User
    {
        if (isset($_SESSION['user']['id'])) {
            $id = (int) $_SESSION['user']['id'];
            return User::findById($id);
        }

        return null;
    }

    public static function check(): bool
    {
        return isset($_SESSION['user']['id']) && self::user() !== null;
    }

    public static function logout(): void
    {
        unset($_SESSION['user']['id']);
        unset($_SESSION['user']);

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
            session_start();
            session_regenerate_id(true);
        }
    }
}
