<?php

namespace App;

enum DealerRequestStatus: string
{
    case Open = 'open';
    case Fulfilled = 'fulfilled';
    case Expired = 'expired';
}
