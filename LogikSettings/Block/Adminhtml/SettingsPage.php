<?php
declare(strict_types=1);

namespace Logik\LogikSettings\Block\Adminhtml;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;

class SettingsPage extends Template
{
    protected ScopeConfigInterface $scopeConfig;

    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->scopeConfig = $scopeConfig;
    }

    public function getLogikUrl()
    {
        $value = $this->scopeConfig->getValue(
            'logik_settings/general/logik_url',
            \Magento\Framework\App\Config\ScopeConfigInterface::SCOPE_TYPE_DEFAULT
        );
        // Add debugging
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/logik_settings.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info('Getting logik_url value: ' . ($value ?: 'null'));
        return $value;
    }

    public function getLogikRuntimeToken()
    {
        $value = $this->scopeConfig->getValue(
            'logik_settings/general/logik_runtime_token',
            \Magento\Framework\App\Config\ScopeConfigInterface::SCOPE_TYPE_DEFAULT
        );
        // Add debugging
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/logik_settings.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info('Getting logik_runtime_token value: ' . ($value ?: 'null'));
        return $value;
    }

    public function getIntegrationToken()
    {
        return $this->scopeConfig->getValue(
            'logik_settings/general/integration_token',
            \Magento\Framework\App\Config\ScopeConfigInterface::SCOPE_TYPE_DEFAULT
        );
    }
} 