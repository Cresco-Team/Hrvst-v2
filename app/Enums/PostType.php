<?php

namespace App\Enums;

enum PostType: string
{
    case Supply = 'supply';
    case Demand = 'demand';
}
