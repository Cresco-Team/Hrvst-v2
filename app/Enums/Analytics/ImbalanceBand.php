<?php

namespace App\Enums;

enum ImbalanceBand: string
{
    case Oversupply = 'oversupply';
    case Balanced = 'balanced';
    case Undersupply = 'undersupply';
}
