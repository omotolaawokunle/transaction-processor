<?php

namespace App\Enums;

enum TransactionStatus: string
{
    case PENDING = 'pending';
    case FAILED = 'failed';
    case COMPLETED = 'completed';
}
