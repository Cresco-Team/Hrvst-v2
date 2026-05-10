<?php

namespace App\Enums;

enum PostStatus: string
{
    case Growing = 'growing';
    case Harvested = 'harvested';
}
