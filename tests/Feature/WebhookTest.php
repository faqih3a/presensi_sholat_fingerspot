<?php

namespace Tests\Feature;

use Tests\TestCase;

class WebhookTest extends TestCase
{
    /**
     * Test webhook empty payload returns 400.
     */
    public function test_webhook_returns_400_for_empty_payload(): void
    {
        $response = $this->postJson('/api/fingerspot-webhook', []);

        $response->assertStatus(400)
                 ->assertJson([
                     'status' => 'error',
                     'message' => 'Empty payload',
                 ]);
    }

    /**
     * Test webhook returns success for unhandled command/type.
     */
    public function test_webhook_returns_success_for_unhandled_payload(): void
    {
        $response = $this->postJson('/api/fingerspot-webhook', [
            'type' => 'unknown_event_type',
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'status' => 'success',
                 ]);
    }
}
