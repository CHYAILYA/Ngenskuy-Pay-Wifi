<?php

namespace App\Controllers\Api;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestCase;

class AIAgentTest extends FeatureTestCase
{
    protected $baseURL = 'http://localhost:8080';

    public function setUp(): void
    {
        parent::setUp();
        
        // Setup test user session
        session()->set('user', [
            'id' => 1,
            'name' => 'Test User',
            'email' => 'test@example.com',
            'role' => 'user'
        ]);
    }

    public function testChatEndpoint()
    {
        $response = $this->get('/agent/chat?message=Hello');
        
        $this->assertResponseStatus(200);
        $this->assertJSONStructure([
            'success',
            'response'
        ]);
    }

    public function testChatWithoutMessage()
    {
        $response = $this->get('/agent/chat');
        
        $this->assertResponseStatus(400);
        $result = json_decode($response, true);
        $this->assertFalse($result['success']);
    }

    public function testPaymentAssistant()
    {
        $response = $this->get('/agent/payment/assistant?message=Bagaimana cara top up?');
        
        $this->assertResponseStatus(200);
        $this->assertJSONStructure([
            'success',
            'response'
        ]);
    }

    public function testPaymentAssistantUnauthorized()
    {
        session()->destroy();
        
        $response = $this->get('/agent/payment/assistant?message=Hello');
        
        $this->assertResponseStatus(401);
    }

    public function testFinanceAnalyze()
    {
        $response = $this->get('/agent/finance/analyze?days=30');
        
        $this->assertResponseStatus(200);
        $result = json_decode($response, true);
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
    }

    public function testFinanceAdvice()
    {
        $response = $this->get('/agent/finance/advice?question=How can I save money?');
        
        $this->assertResponseStatus(200);
        $this->assertJSONStructure([
            'success',
            'response'
        ]);
    }

    public function testQuickActions()
    {
        $response = $this->get('/agent/quick-actions');
        
        $this->assertResponseStatus(200);
        $result = json_decode($response, true);
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
    }

    public function testInsights()
    {
        $response = $this->get('/agent/insights');
        
        $this->assertResponseStatus(200);
        $result = json_decode($response, true);
        $this->assertTrue($result['success']);
    }

    public function testSuggestions()
    {
        $response = $this->get('/agent/suggestions');
        
        $this->assertResponseStatus(200);
        $result = json_decode($response, true);
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
    }

    public function testClearHistory()
    {
        $response = $this->get('/agent/clear-history');
        
        $this->assertResponseStatus(200);
        $result = json_decode($response, true);
        $this->assertTrue($result['success']);
    }

    public function testDeviceCommand()
    {
        $response = $this->get('/agent/device/command?command=Turn on the light&execute=false');
        
        $this->assertResponseStatus(200);
        $this->assertJSONStructure([
            'success'
        ]);
    }

    public function testDeviceList()
    {
        $response = $this->get('/agent/device/list');
        
        $this->assertResponseStatus(200);
        $result = json_decode($response, true);
        $this->assertTrue($result['success']);
    }

    public function testVoiceCommand()
    {
        $response = $this->get('/agent/voice?text=Turn on the light');
        
        $this->assertResponseStatus(200);
        $this->assertJSONStructure([
            'success'
        ]);
    }

    public function testNotifications()
    {
        $response = $this->get('/agent/notifications');
        
        $this->assertResponseStatus(200);
        $result = json_decode($response, true);
        $this->assertTrue($result['success']);
    }

    public function testSummary()
    {
        $response = $this->get('/agent/summary');
        
        $this->assertResponseStatus(200);
        $result = json_decode($response, true);
        $this->assertTrue($result['success']);
    }

    protected function assertJSONStructure(array $keys)
    {
        $response = json_decode($this->response, true);
        
        foreach ($keys as $key) {
            $this->assertArrayHasKey($key, $response);
        }
    }
}
