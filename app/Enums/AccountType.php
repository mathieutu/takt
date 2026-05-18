<?php

namespace App\Enums;

enum AccountType: string
{
    case User = 'user';
    case Organization = 'organization';
}
