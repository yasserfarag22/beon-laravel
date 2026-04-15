<?php

namespace Beon\Laravel\Tests\Unit;

use Beon\Laravel\BeonClient;
use Beon\Laravel\BeonManager;
use Beon\Laravel\Tests\TestCase;
use Mockery;

class BeonManagerTest extends TestCase
{
    public function test_send_message_calls_client_with_correct_data(): void
    {
        $client = Mockery::mock(BeonClient::class);
        $manager = new BeonManager($client);

        $to = '201000830792';
        $name = 'Yasser Farag';
        $templateId = 1234;
        $templateContent = 'أهلاً {{1}}، كود التحقق هو {{2}}';
        $templateJson = [
            'name' => 'welcome_msg',
            'language' => ['code' => 'ar'],
            'components' => [
                [
                    'type' => 'body',
                    'parameters' => [
                        ['type' => 'text', 'text' => 'Yasser'],
                        ['type' => 'text', 'text' => '5566']
                    ]
                ]
            ]
        ];

        $client->shouldReceive('post')
            ->once()
            ->with('/api/v3/messages/whatsapp/template', [
                'phoneNumber'      => $to,
                'name'             => $name,
                'template_id'      => $templateId,
                'template_content' => $templateContent,
                'template'         => $templateJson,
                'custom_attribute' => [],
            ])
            ->andReturn(['success' => true]);

        $response = $manager->sendMessage($to, $name, $templateId, $templateContent, $templateJson);

        $this->assertTrue($response['success']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
