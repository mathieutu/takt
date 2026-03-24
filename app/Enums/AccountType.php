<?php

namespace App\Enum;

enum AccountType: string
{
    case User = 'user';
    case Organization = 'organization';
}
