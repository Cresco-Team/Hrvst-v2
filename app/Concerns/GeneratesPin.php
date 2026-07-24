<?php

namespace App\Concerns;

trait GeneratesPin
{
    protected function generatePin(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }
}
