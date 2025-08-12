<?php

namespace App\Enum;

enum RoleUser: string
{
    case ROLE_USER = 'ROLE_USER';
    case ROLE_MANAGER = 'ROLE_MANAGER';
    case ROLE_ADMIN = 'ROLE_ADMIN';
    case ROLE_SUPER_ADMIN = 'ROLE_SUPER_ADMIN';

    public function getRole(): string
    {
        return $this->value;
    }
}