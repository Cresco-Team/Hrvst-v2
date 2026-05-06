<?php

namespace App\Enums;

enum PostStatus: string
{
    case Growing = 'growing';
    case Ongoing = 'ongoing';
    case Archived = 'archived';
    case Fulfilled = 'fulfilled';
}
