<?php

namespace App\Exceptions\Billing;

use Exception;
use Illuminate\Foundation\Exceptions\ShouldntReport;

class PaymentFailedException extends Exception implements ShouldntReport {}
