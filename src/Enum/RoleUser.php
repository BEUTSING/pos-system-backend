<?php

namespace App\Enum;

enum RoleUser: string
{
    case ROLE_WAITER = 'ROLE_WAITER';
    case ROLE_MANAGER = 'ROLE_MANAGER';
    case ROLE_ADMIN = 'ROLE_ADMIN';
    case ROLE_TELLER = 'ROLE_TELLER';


}