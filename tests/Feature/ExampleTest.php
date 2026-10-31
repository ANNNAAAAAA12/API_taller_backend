<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_checkout_page_is_available(): void
    {
        $this->get('/checkout')->assertOk();
    }

    public function test_email_page_is_available(): void
    {
        $this->get('/email')->assertOk();
    }

    public function test_payment_intent_requires_valid_data(): void
    {
        $this->postJson('/create-payment-intent', [])
            ->assertUnprocessable();
    }
}
