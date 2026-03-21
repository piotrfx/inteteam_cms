<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

final class ExampleTest extends TestCase
{
    public function test_root_redirects_to_login(): void
    {
        $this->get('/')->assertRedirect(route('admin.login'));
    }

    public function test_login_page_is_accessible(): void
    {
        $this->get(route('admin.login'))->assertStatus(200);
    }
}
