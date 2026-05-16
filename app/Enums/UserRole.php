<?php

namespace App\Enums;

enum UserRole: string
{
    case Reader = 'reader';
    case Librarian = 'librarian';
    case Admin = 'admin';

    public static function values(): array
    {
        return array_map(static fn (self $role) => $role->value, self::cases());
    }
}
