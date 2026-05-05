<?php

namespace App\Enums;

enum PostStatus: string
{
    case Growing = 'Growing';
    case Ongoing = 'Ongoing';
    case Archived = 'Archived';
    case Fulfilled = 'Fulfilled';
}
