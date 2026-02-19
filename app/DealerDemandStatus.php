<?php

namespace App;

enum DealerDemandStatus: string
{
    case Open = 'open';
    case Fulfilled = 'fulfilled';
    case Expired = 'expired';
}
