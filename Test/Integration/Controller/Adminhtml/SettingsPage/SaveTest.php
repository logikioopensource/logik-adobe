<?php
declare(strict_types=1);

namespace Logik\Logik\Test\Integration\Controller\Adminhtml\SettingsPage;

use Magento\TestFramework\TestCase\AbstractBackendController;
use Magento\Framework\App\Request\Http as HttpRequest;

class SaveTest extends AbstractBackendController
{
    /**
     * @var string
     */
    protected $resource = 'Logik_LogikSettings::logik_settingspage';

    /**
     * @var string
     */
    protected $uri = 'backend/logik_settings/settingspage/save';

    /**
     * @magentoAppArea adminhtml
     * @magentoDbIsolation enabled
     */
    public function testAclHasAccess()
    {
        $this->_objectManager->get(\Magento\Framework\Acl\Builder::class)
            ->getAcl()
            ->allow(null, $this->resource);

        $this->getRequest()
            ->setMethod(HttpRequest::METHOD_POST)
            ->setParams([
                'logik_url' => 'https://test.dev.logik.io',
                'logik_runtime_token' => 'test_token'
            ]);

        $this->dispatch($this->uri);
        $this->assertNotSame(404, $this->getResponse()->getHttpResponseCode());
    }

    /**
     * @magentoAppArea adminhtml
     * @magentoDbIsolation enabled
     */
    public function testAclNoAccess()
    {
        $this->_objectManager->get(\Magento\Framework\Acl\Builder::class)
            ->getAcl()
            ->deny(null, $this->resource);

        $this->getRequest()
            ->setMethod(HttpRequest::METHOD_POST)
            ->setParams([
                'logik_url' => 'https://test.dev.logik.io',
                'logik_runtime_token' => 'test_token'
            ]);

        $this->dispatch($this->uri);
        $this->assertSame(403, $this->getResponse()->getHttpResponseCode());
    }

    /**
     * @magentoAppArea adminhtml
     * @magentoDbIsolation enabled
     * @magentoConfigFixture default/logik_settings/general/enabled 1
     */
    public function testSaveAction()
    {
        $this->_objectManager->get(\Magento\Framework\Acl\Builder::class)
            ->getAcl()
            ->allow(null, $this->resource);

        $testUrl = 'https://test.dev.logik.io';
        $testToken = 'test_token';

        $this->getRequest()
            ->setMethod(HttpRequest::METHOD_POST)
            ->setParams([
                'logik_url' => $testUrl,
                'logik_runtime_token' => $testToken
            ]);

        $this->dispatch($this->uri);

        // Assert response is redirect
        $this->assertRedirect($this->stringContains('backend/logik_settings/settingspage'));

        // Verify config values were saved
        $configValue = $this->_objectManager->get(\Magento\Framework\App\Config\ScopeConfigInterface::class)
            ->getValue('logik_settings/general/logik_url');
        $this->assertEquals($testUrl, $configValue);

        $configToken = $this->_objectManager->get(\Magento\Framework\App\Config\ScopeConfigInterface::class)
            ->getValue('logik_settings/general/logik_runtime_token');
        $this->assertEquals($testToken, $configToken);
    }

    /**
     * @magentoAppArea adminhtml
     * @magentoDbIsolation enabled
     */
    public function testInvalidUrl()
    {
        $this->_objectManager->get(\Magento\Framework\Acl\Builder::class)
            ->getAcl()
            ->allow(null, $this->resource);

        $this->getRequest()
            ->setMethod(HttpRequest::METHOD_POST)
            ->setParams([
                'logik_url' => 'invalid-url',
                'logik_runtime_token' => 'test_token'
            ]);

        $this->dispatch($this->uri);

        // Should still redirect but with error message
        $this->assertRedirect($this->stringContains('backend/logik_settings/settingspage'));
    }
} 