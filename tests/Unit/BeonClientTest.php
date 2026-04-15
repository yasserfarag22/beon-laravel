<?php

namespace Beon\Laravel\Tests\Unit;

use Beon\Laravel\BeonClient;
use Beon\Laravel\Tests\TestCase;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

class BeonClientTest extends TestCase
{
    private function makeClient(array $responses): BeonClient
    {
        $mock    = new MockHandler($responses);
        $handler = HandlerStack::create($mock);

        return new BeonClient('https://v3.api.beon.chat', 'test-key', 30, $handler);
    }

    public function test_post_returns_parsed_json(): void
    {
        $client = $this->makeClient([
            new Response(200, [], json_encode(['data' => 'ok'])),
        ]);

        $result = $client->post('/api/v3/test', ['foo' => 'bar']);

        $this->assertTrue($result['success']);
        $this->assertEquals(200, $result['status_code']);
        $this->assertEquals('ok', $result['data']);
    }

    public function test_post_handles_api_error(): void
    {
        $client = $this->makeClient([
            new Response(400, [], json_encode(['error' => 'Bad request'])),
        ]);

        $result = $client->post('/api/v3/test', []);

        $this->assertEquals(400, $result['status_code']);
        $this->assertEquals('Bad request', $result['error']);
    }

    public function test_get_returns_parsed_json(): void
    {
        $client = $this->makeClient([
            new Response(200, [], json_encode(['items' => [1, 2, 3]])),
        ]);

        $result = $client->get('/api/v3/items');

        $this->assertTrue($result['success']);
        $this->assertCount(3, $result['items']);
    }
}
