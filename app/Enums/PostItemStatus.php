<?php

namespace App\Enums;

enum PostItemStatus: string
{
    case Ongoing = 'ongoing';
    case Fulfilled = 'fulfilled';
    case Expired = 'expired';
}
