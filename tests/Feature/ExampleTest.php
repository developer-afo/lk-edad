<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_home_asks_for_sign_in(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }

    public function test_login_screen_is_available(): void
    {
        $this->get('/login')->assertOk()->assertSee('Sign in with TribePeer');
    }
}
