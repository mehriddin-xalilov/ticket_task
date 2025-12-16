<?php

namespace App\Helpers;

class Roles
{
    const ROLE_SUPER_ADMIN = 'super_admin'; //Super admin

    const ROLE_ADMIN = 'admin'; // Admin
    const ROLE_COMPANY = 'company'; // Company
    const ROLE_STUDENT = 'student'; // Students


    const ADMINS = self::ROLE_SUPER_ADMIN . ',' . self::ROLE_ADMIN;


    public static function asString(): string
    {
        return implode(',', [
            self::ROLE_SUPER_ADMIN,
            self::ROLE_ADMIN,
            self::ROLE_COMPANY,
            self::ROLE_STUDENT,
        ]);
    }

    public static function asArray()
    {
        return [
            self::ROLE_SUPER_ADMIN,
            self::ROLE_ADMIN,
            self::ROLE_COMPANY,
            self::ROLE_STUDENT,
        ];
    }



}
