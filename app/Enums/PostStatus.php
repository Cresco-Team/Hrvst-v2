<?php

namespace App\Enums;

enum PostStatus: string
{
    case Growing = 'growing';
    case Ready = 'ready for harvest';
}
