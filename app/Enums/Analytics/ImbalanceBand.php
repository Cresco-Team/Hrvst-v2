<?php

namespace App\Enums\Analytics;

enum ImbalanceBand: string
{
    case Oversupply = 'oversupply';
    case Balanced = 'balanced';
    case Undersupply = 'undersupply';
}
