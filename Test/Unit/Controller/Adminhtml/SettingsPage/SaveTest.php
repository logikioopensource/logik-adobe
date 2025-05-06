<?php
declare(strict_types=1);

namespace Logik\Logik\Test\Unit\Controller\Adminhtml\SettingsPage;

use Logik\Logik\Controller\Adminhtml\SettingsPage\Save;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Integration\Api\IntegrationServiceInterface;
use Magento\Integration\Api\AuthorizationServiceInterface;
use Magento\Integration\Api\OauthServiceInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class SaveTest extends TestCase
{
    /** @var Save */
    private $saveController;

    /** @var Context|MockObject */
    private $contextMock;

    /** @var WriterInterface|MockObject */
    private $configWriterMock;

    /** @var IntegrationServiceInterface|MockObject */
    private $integrationServiceMock;

    /** @var AuthorizationServiceInterface|MockObject */
    private $authorizationServiceMock;

    /** @var OauthServiceInterface|MockObject */
    private $oauthServiceMock;

    /** @var StoreManagerInterface|MockObject */
    private $storeManagerMock;

    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);

        // Create message manager mock
        $messageManager = $this->createMock(\Magento\Framework\Message\ManagerInterface::class);
        
        $this->contextMock = $this->createMock(Context::class);
        $this->contextMock->expects($this->any())
            ->method('getMessageManager')
            ->willReturn($messageManager);

        $this->configWriterMock = $this->createMock(WriterInterface::class);
        $this->integrationServiceMock = $this->createMock(IntegrationServiceInterface::class);
        $this->authorizationServiceMock = $this->createMock(AuthorizationServiceInterface::class);
        $this->oauthServiceMock = $this->createMock(OauthServiceInterface::class);
        $this->storeManagerMock = $this->createMock(StoreManagerInterface::class);

        $this->saveController = $objectManager->getObject(
            Save::class,
            [
                'context' => $this->contextMock,
                'configWriter' => $this->configWriterMock,
                'integrationService' => $this->integrationServiceMock,
                'authorizationService' => $this->authorizationServiceMock,
                'oauthService' => $this->oauthServiceMock,
                'storeManager' => $this->storeManagerMock
            ]
        );
    }

    public function testIsAllowed()
    {
        $authorization = $this->createMock(\Magento\Framework\Authorization::class);
        $authorization->expects($this->once())
            ->method('isAllowed')
            ->with('Logik_Logik::logik_settingspage')
            ->willReturn(true);
        
        $context = $this->createMock(\Magento\Backend\App\Action\Context::class);
        $context->expects($this->once())
            ->method('getAuthorization')
            ->willReturn($authorization);
        
        // Recreate controller with mocked context
        $this->saveController = new Save(
            $context,
            $this->configWriterMock,
            $this->integrationServiceMock,
            $this->authorizationServiceMock,
            $this->oauthServiceMock,
            $this->storeManagerMock
        );
        
        // Use reflection to access protected method
        $reflection = new \ReflectionClass(get_class($this->saveController));
        $method = $reflection->getMethod('_isAllowed');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->saveController);
        $this->assertTrue($result);
    }

    public function testSaveConfigurationValues()
    {
        $params = [
            'logik_url' => 'https://test.dev.logik.io',
            'logik_runtime_token' => 'test_token'
        ];

        $this->configWriterMock->expects($this->exactly(2))
            ->method('save')
            ->withConsecutive(
                [
                    'logik_settings/general/logik_url',
                    $params['logik_url'],
                    'default',
                    0
                ],
                [
                    'logik_settings/general/logik_runtime_token',
                    $params['logik_runtime_token'],
                    'default',
                    0
                ]
            );

        // Use reflection to access protected method
        $reflection = new \ReflectionClass(get_class($this->saveController));
        $method = $reflection->getMethod('saveConfigurationValues');
        $method->setAccessible(true);
        
        $method->invoke($this->saveController, $params);
    }

    public function testExtractTenantInfo()
    {
        $reflection = new \ReflectionClass(get_class($this->saveController));
        $method = $reflection->getMethod('extractTenantInfo');
        $method->setAccessible(true);

        $result = $method->invoke($this->saveController, 'https://dev1.dev.logik.io');
        
        $this->assertEquals([
            'tenant' => 'dev1',
            'sector' => 'dev'
        ], $result);
    }

    public function testExtractTenantInfoWithInvalidUrl()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid Logik URL format');

        $reflection = new \ReflectionClass(get_class($this->saveController));
        $method = $reflection->getMethod('extractTenantInfo');
        $method->setAccessible(true);

        $method->invoke($this->saveController, 'https://invalid.logik.io');
    }

    public function testGenerateTokens()
    {
        $integrationId = 1;
        $consumerId = 123;
        
        $integration = $this->getMockBuilder(\Magento\Integration\Model\Integration::class)
            ->disableOriginalConstructor()
            ->addMethods(['getConsumerId'])
            ->onlyMethods(['getId'])
            ->getMock();
        
        $integration->expects($this->any())
            ->method('getConsumerId')
            ->willReturn($consumerId);
        $integration->expects($this->any())
            ->method('getId')
            ->willReturn($integrationId);

        $this->oauthServiceMock->expects($this->once())
            ->method('createAccessToken')
            ->with($consumerId);

        $this->integrationServiceMock->expects($this->once())
            ->method('get')
            ->with($integrationId)
            ->willReturn($integration);

        $reflection = new \ReflectionClass(get_class($this->saveController));
        $method = $reflection->getMethod('generateTokens');
        $method->setAccessible(true);

        $result = $method->invoke($this->saveController, $integration);
        $this->assertSame($integration, $result);
    }

    public function testHandleExistingIntegration()
    {
        $integrationId = 1;
        $token = 'test_token';
        
        $integration = $this->getMockBuilder(\Magento\Integration\Model\Integration::class)
            ->disableOriginalConstructor()
            ->addMethods(['getToken'])
            ->onlyMethods(['getStatus', 'getId'])
            ->getMock();
        
        $integration->expects($this->any())
            ->method('getStatus')
            ->willReturn(\Magento\Integration\Model\Integration::STATUS_ACTIVE);
        $integration->expects($this->any())
            ->method('getId')
            ->willReturn($integrationId);
        $integration->expects($this->any())
            ->method('getToken')
            ->willReturn($token);

        $reflection = new \ReflectionClass(get_class($this->saveController));
        $method = $reflection->getMethod('handleExistingIntegration');
        $method->setAccessible(true);

        $result = $method->invoke($this->saveController, $integration);
        $this->assertSame($integration, $result);
    }

    public function testCreateNewIntegration()
    {
        $integrationId = 1;
        $integrationData = [
            'name' => 'logik',
            'email' => 'logik@example.com',
            'status' => \Magento\Integration\Model\Integration::STATUS_ACTIVE,
            'endpoint' => 'https://test.dev.logik.io',
            'setup_type' => \Magento\Integration\Model\Integration::TYPE_CONFIG
        ];
        
        $params = [
            'logik_url' => 'https://test.dev.logik.io'
        ];

        // Create message manager mock
        $messageManager = $this->createMock(\Magento\Framework\Message\ManagerInterface::class);
        $this->contextMock->expects($this->any())
            ->method('getMessageManager')
            ->willReturn($messageManager);

        $integration = $this->getMockBuilder(\Magento\Integration\Model\Integration::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId'])
            ->getMock();
        $integration->expects($this->any())
            ->method('getId')
            ->willReturn($integrationId);

        $this->integrationServiceMock->expects($this->once())
            ->method('create')
            ->with($integrationData)
            ->willReturn($integration);

        $this->authorizationServiceMock->expects($this->once())
            ->method('grantAllPermissions')
            ->with($integrationId);

        $reflection = new \ReflectionClass(get_class($this->saveController));
        $method = $reflection->getMethod('createNewIntegration');
        $method->setAccessible(true);

        $result = $method->invoke($this->saveController, $params);
        $this->assertSame($integration, $result);
    }

    // Add more test methods here
}
