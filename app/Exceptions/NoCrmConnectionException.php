<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

final class NoCrmConnectionException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('No CRM connection configured for this company.');
    }
}
