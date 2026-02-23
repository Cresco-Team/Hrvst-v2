<?php

namespace App\Enums;

enum PostPriceFlag: string
{
    case Low = 'Low';
    case Fair = 'Fair';
    case High = 'High';
}