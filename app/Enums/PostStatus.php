<?php

namespace App\Enums;

enum PostStatus: string
{
    case Ongoing = 'Ongoing';
    case Archived = 'Archived';
    case Fulfilled = 'Fulfilled';
}