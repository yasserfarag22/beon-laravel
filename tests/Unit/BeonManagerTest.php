<?php

namespace Beon\Laravel\Tests\Unit;

use Beon\Laravel\BeonClient;
use Beon\Laravel\BeonManager;
use Beon\Laravel\Exceptions\ApiException;
use Beon\Laravel\Tests\TestCase;
use Mockery;

class BeonManagerTest extends TestCase
{
    protected $client;
    protected $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = Mockery::mock(BeonClient::class);
        $this->manager = new BeonManager($this->client);
    }

    public function test_fluent_to_method_returns_message_builder(): void
    {
        $message = $this->manager->to('201000830792');
        $this->assertInstanceOf(\Beon\Laravel\Message::class, $message);
    }

    public function test_send_message_via_fluent_api(): void
    {
        $to = '201000830792';
        $this->client->shouldReceive('post')
            ->once()
            ->with('/api/v3/messages/whatsapp/template', Mockery::on(function ($payload) use ($to) {
                return $payload['phoneNumber'] === $to && $payload['template']['name'] === 'welcome_msg';
            }))
            ->andReturn(['success' => true]);

        $response = $this->manager->to($to)
            ->template('welcome_msg')
            ->withVariables(['Yasser'])
            ->send();

        $this->assertTrue($response['success']);
    }

    public function test_send_text_message_via_fluent_api(): void
    {
        $to = '201000830792';
        
        // Mock conversation resolution
        $this->client->shouldReceive('post')
            ->once()
            ->with('/api/v3/conversation/start', [
                'phone' => $to,
                'name'  => 'Customer',
                'channel_id' => 1,
            ])
            ->andReturn(['data' => ['conversation_id' => 456]]);

        $this->client->shouldReceive('post')
            ->once()
            ->with('/api/v3/conversation/message/send', Mockery::on(function ($payload) {
                return $payload['type'] === 'text' && $payload['content'] === 'Hello' && $payload['conversation_id'] === 456 && $payload['channel_id'] === 1;
            }))
            ->andReturn(['success' => true]);

        $response = $this->manager->to($to)
            ->text('Hello')
            ->send();

        $this->assertTrue($response['success']);
    }

    public function test_send_image_message_via_fluent_api(): void
    {
        $to = '201000830792';
        
        $this->client->shouldReceive('post')
            ->once()
            ->with('/api/v3/conversation/start', [
                'phone' => $to,
                'name'  => 'Customer',
                'channel_id' => 1,
            ])
            ->andReturn(['data' => ['conversation_id' => 456]]);

        $this->client->shouldReceive('post')
            ->once()
            ->with('/api/v3/conversation/message/send', Mockery::on(function ($payload) {
                return $payload['type'] === 'image' && $payload['media_url'] === 'http://example.com/a.jpg' && $payload['channel_id'] === 1;
            }))
            ->andReturn(['success' => true]);

        $response = $this->manager->to($to)
            ->image('http://example.com/a.jpg')
            ->send();

        $this->assertTrue($response['success']);
    }

    public function test_react_to_message(): void
    {
        $this->client->shouldReceive('post')
            ->once()
            ->with('/api/v3/conversation/react', [
                'message_id' => 'mid.123',
                'conversation_id' => 456,
                'channel_id' => 1,
                'react' => '👍',
            ])
            ->andReturn(['success' => true]);

        $response = $this->manager->react('mid.123', '👍', '201000830792', 456);

        $this->assertTrue($response['success']);
    }


    public function test_list_templates(): void
    {
        $this->client->shouldReceive('get')
            ->once()
            ->with('/api/v3/template')
            ->andReturn(['data' => []]);

        $response = $this->manager->templates();

        $this->assertIsArray($response);
    }

    public function test_api_exception_is_thrown_on_error(): void
    {
        $this->client->shouldReceive('post')
            ->andThrow(new ApiException('API Error', 401));

        $this->expectException(ApiException::class);
        $this->expectExceptionCode(401);

        $this->manager->to('201000830792')->template('test')->send();
    }

    public function test_legacy_send_message_still_works(): void
    {
        $this->client->shouldReceive('post')
            ->once()
            ->andReturn(['success' => true]);

        $response = $this->manager->sendMessage(
            '201012345678',
            'John',
            123,
            'Hello',
            ['name' => 'test_tpl', 'language' => ['code' => 'ar'], 'components' => []]
        );

        $this->assertTrue($response['success']);
    }


    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}

