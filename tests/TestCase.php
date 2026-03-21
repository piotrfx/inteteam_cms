<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // CSRF is verified at the browser/E2E layer.
        // Disable it in PHPUnit so JSON test requests don't need tokens.
        $this->withoutMiddleware(ValidateCsrfToken::class);
    }
}
