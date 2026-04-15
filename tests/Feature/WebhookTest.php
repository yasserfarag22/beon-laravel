<?php

// Author: Yasser Farag Abdelhamid — Backend Engineer @ Beon
// WhatsApp: +201000830792

namespace Beon\Laravel\Tests\Feature;

use Beon\Laravel\Events\MessageReceived;
use Beon\Laravel\Events\MessageStatusUpdated;
use Beon\Laravel\Tests\TestCase;
use Illuminate\Support\Facades\Event;

class WebhookTest extends TestCase
{
    private function webhookPayload(array $message): array
    {
        return [
            'object' => 'whatsapp_business_account',
            'entry'  => [[
                'id'      => 'ENTRY_ID',
                'changes' => [[
                    'value' => [
                        'messaging_product' => 'whatsapp',
                        'metadata'          => ['phone_number_id' => 'PHONE_NUMBER_ID'],
                        'messages'          => [$message],
                    ],
                    'field' => 'messages',
                ]],
            ]],
        ];
    }

    public function test_webhook_verification_succeeds_with_correct_token(): void
    {
        $response = $this->get('/beon/webhook?hub_verify_token=test-secret&hub_challenge=CHALLENGE_123', [
            'Accept' => 'text/plain',
        ]);

        $response->assertStatus(200);
        $response->assertSee('CHALLENGE_123');
    }

    public function test_webhook_verification_fails_with_wrong_token(): void
    {
        $response = $this->get('/beon/webhook?hub_verify_token=wrong-token&hub_challenge=CHALLENGE');
        $response->assertStatus(403);
    }

    public function test_text_message_fires_message_received_event(): void
    {
        Event::fake();

        $this->post('/beon/webhook', $this->webhookPayload([
            'id'        => 'wamid.001',
            'from'      => '201000830792',
            'timestamp' => '1712000000',
            'type'      => 'text',
            'text'      => ['body' => 'Hello!'],
        ]))->assertStatus(200);

        Event::assertDispatched(MessageReceived::class, function ($event) {
            return $event->payload['type'] === 'text'
                && $event->payload['text'] === 'Hello!';
        });
    }

    public function test_image_message_fires_event_with_media_id(): void
    {
        Event::fake();

        $this->post('/beon/webhook', $this->webhookPayload([
            'id'        => 'wamid.002',
            'from'      => '201000830792',
            'timestamp' => '1712000001',
            'type'      => 'image',
            'image'     => ['id' => 'media_id_abc', 'mime_type' => 'image/jpeg', 'caption' => 'Look!'],
        ]))->assertStatus(200);

        Event::assertDispatched(MessageReceived::class, function ($event) {
            return $event->payload['type'] === 'image'
                && $event->payload['media_id'] === 'media_id_abc'
                && $event->payload['caption'] === 'Look!';
        });
    }

    public function test_interactive_button_reply_is_parsed(): void
    {
        Event::fake();

        $this->post('/beon/webhook', $this->webhookPayload([
            'id'        => 'wamid.003',
            'from'      => '201000830792',
            'timestamp' => '1712000002',
            'type'      => 'interactive',
            'interactive' => [
                'type'         => 'button_reply',
                'button_reply' => ['id' => '1', 'title' => 'Yes ✅'],
            ],
        ]))->assertStatus(200);

        Event::assertDispatched(MessageReceived::class, function ($event) {
            return $event->payload['reply_type'] === 'button'
                && $event->payload['reply_title'] === 'Yes ✅';
        });
    }

    public function test_status_update_fires_correct_event(): void
    {
        Event::fake();

        $payload = [
            'object' => 'whatsapp_business_account',
            'entry'  => [[
                'id'      => 'ENTRY_ID',
                'changes' => [[
                    'value' => [
                        'statuses' => [[
                            'id'           => 'wamid.xxx',
                            'status'       => 'delivered',
                            'timestamp'    => '1712000003',
                            'recipient_id' => '201000830792',
                        ]],
                    ],
                    'field' => 'messages',
                ]],
            ]],
        ];

        $this->post('/beon/webhook', $payload)->assertStatus(200);

        Event::assertDispatched(MessageStatusUpdated::class, function ($event) {
            return $event->payload['status'] === 'delivered'
                && $event->payload['message_id'] === 'wamid.xxx';
        });
    }

    public function test_unsupported_message_type_is_skipped(): void
    {
        Event::fake();

        $this->post('/beon/webhook', $this->webhookPayload([
            'id'   => 'wamid.004',
            'from' => '201000830792',
            'type' => 'unsupported',
        ]))->assertStatus(200);

        Event::assertNotDispatched(MessageReceived::class);
    }

    protected function defineRoutes($router): void
    {
        $router->beonWebhook('/beon/webhook');
    }
}
