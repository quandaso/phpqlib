<?php
/**
 * @author quantm
 * @date: 5/10/2017 6:49 PM
 */

namespace Q\Helpers;


class Password
{
    const SALT_VALUE = 'YPgEtvt0bMEe#wa3';

    /**
     * Generate user password
     * @return string
     */
    public static function hashPassword($password): string
    {
        return password_hash($password, PASSWORD_BCRYPT);
    }

    /**
     * Verify password
     * @param $password
     * @param $hash
     * @param $method
     * @return bool
     */
    public static function verifyPassword($password, $hash)
    {
        return password_verify($password, $hash);
    }
}
