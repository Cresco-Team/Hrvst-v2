<?php

namespace App\Enums;

enum DealerDemandStatus: string
{
    case Open = 'open';
    case Fulfilled = 'fulfilled';
    case Expired = 'expired';
}
