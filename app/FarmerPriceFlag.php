<?php

namespace App;

enum FarmerPriceFlag: string
{
    case Lean = 'lean';
    case Fair = 'fair';
    case High = 'high';
}
