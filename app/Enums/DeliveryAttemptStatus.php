<?php

namespace App\Enums;

enum DeliveryAttemptStatus: string
{
    case Pending = 'pending';
    case Sent = 'sent';
    case Failed = 'failed';
}
