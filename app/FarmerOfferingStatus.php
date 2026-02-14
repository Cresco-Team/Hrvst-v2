<?php

namespace App;

enum FarmerOfferingStatus: string
{
    case Available = 'available';
    case Archived = 'archived';
}
