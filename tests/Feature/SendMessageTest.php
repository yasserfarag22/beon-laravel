<?php

// Author: Yasser Farag Abdelhamid — Backend Engineer @ Beon
// WhatsApp: +201000830792

namespace Beon\Laravel\Tests\Feature;

use Beon\Laravel\BeonClient;
use Beon\Laravel\BeonManager;
use Beon\Laravel\Tests\TestCase;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

class SendMessageTest extends TestCase
{
    private function makeManager(array $responses): BeonManager
    {
        $mock    = new MockHandler($responses);
        $handler = HandlerStack::create($mock);
        $client  = new BeonClient('https://v3.api.beon.chat', 'test-key', 30, $handler);

        return new BeonManager($client);
    }

    public function test_send_text_calls_correct_endpoint(): void
    {
        $manager = $this->makeManager([
            new Response(200, [], json_encode(['success' => true, 'message_id' => 'wamid.xxx'])),
        ]);

        $result = $manager->sendText('201000830792', 'Hello 👋');

        $this->assertTrue($result['success']);
        $this->assertEquals(200, $result['status_code']);
    }

    public function test_send_template_includes_name_and_components(): void
    {
        $manager = $this->makeManager([
            new Response(200, [], json_encode(['success' => true, 'message_id' => 'wamid.yyy'])),
        ]);

        $result = $manager->sendTemplate('201000830792', 'welcome_msg', [
            ['type' => 'body', 'parameters' => [['type' => 'text', 'text' => 'Ahmed']]],
        ], 'ar');

        $this->assertTrue($result['success']);
    }

    public function test_send_media_by_url(): void
    {
        $manager = $this->makeManager([
            new Response(200, [], json_encode(['success' => true])),
        ]);

        $result = $manager->sendMedia('201000830792', 'image', 'https://example.com/img.jpg', 'Hello!');

        $this->assertEquals(200, $result['status_code']);
    }

    public function test_send_interactive_buttons(): void
    {
        $manager = $this->makeManager([
            new Response(200, [], json_encode(['success' => true])),
        ]);

        $result = $manager->sendInteractive('201000830792', 'Do you confirm?', [
            ['type' => 'reply', 'reply' => ['id' => '1', 'title' => 'Yes ✅']],
            ['type' => 'reply', 'reply' => ['id' => '2', 'title' => 'No ❌']],
        ]);

        $this->assertEquals(200, $result['status_code']);
    }

    public function test_send_reaction(): void
    {
        $manager = $this->makeManager([
            new Response(200, [], json_encode(['success' => true])),
        ]);

        $result = $manager->sendReaction('201000830792', 'wamid.abc', '👍');

        $this->assertEquals(200, $result['status_code']);
    }
}
