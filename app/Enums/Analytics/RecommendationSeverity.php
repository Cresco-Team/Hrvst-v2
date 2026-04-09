<?php

namespace App\Enums;

enum RecommendationSeverity: string
{
    case Critical = 'critical';
    case Warning = 'warning';
    case Info = 'info';
}
