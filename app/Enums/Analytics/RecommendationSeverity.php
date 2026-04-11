<?php

namespace App\Enums\Analytics;

enum RecommendationSeverity: string
{
    case Critical = 'critical';
    case Warning = 'warning';
    case Info = 'info';
}
