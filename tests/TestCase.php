<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        // Force test database — Docker env_file overrides .env.testing,
        // so we must set it before the app boots.
        putenv('DB_DATABASE=inteteam_cms_test');
        $_ENV['DB_DATABASE'] = 'inteteam_cms_test';
        $_SERVER['DB_DATABASE'] = 'inteteam_cms_test';

        parent::setUp();

        // CSRF is verified at the browser/E2E layer.
        // Disable it in PHPUnit so JSON test requests don't need tokens.
        $this->withoutMiddleware(ValidateCsrfToken::class);
    }
}
