<?php
declare(strict_types=1);

namespace Logik\Logik\Block\Adminhtml;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class SettingsPage extends Template
{
    protected $scopeConfig;

    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        array $data = []
    ) {
        // Add detailed logging
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/logik_settings.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info('SettingsPage block constructor called');
        
        $this->scopeConfig = $scopeConfig;
        parent::__construct($context, $data);
    }

    public function getLogikUrl()
    {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/logik_settings.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info('getLogikUrl called');
        
        return $this->scopeConfig->getValue('logik/general/url', ScopeInterface::SCOPE_STORE);
    }

    public function getRuntimeToken()
    {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/logik_settings.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info('getRuntimeToken called');
        
        return $this->scopeConfig->getValue('logik/general/runtime_token', ScopeInterface::SCOPE_STORE);
    }

    public function getIntegrationToken()
    {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/logik_settings.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info('getIntegrationToken called');
        
        return $this->scopeConfig->getValue('logik/general/integration_token', ScopeInterface::SCOPE_STORE);
    }
} 