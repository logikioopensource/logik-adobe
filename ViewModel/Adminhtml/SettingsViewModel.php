<?php
declare(strict_types=1);

namespace Logik\Integration\ViewModel\Adminhtml;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;

class SettingsViewModel implements ArgumentInterface
{
    private ScopeConfigInterface $scopeConfig;
    private LoggerInterface $logger;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
    }

    public function getLogikUrl(): string
    {
        $this->logger->info('getLogikUrl called');
        return (string)$this->scopeConfig->getValue(
            'logik_settings/general/logik_url',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getLogikRuntimeToken(): string
    {
        $this->logger->info('getLogikRuntimeToken called');
        return (string)$this->scopeConfig->getValue(
            'logik_settings/general/logik_runtime_token',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getIntegrationToken(): string
    {
        $this->logger->info('getIntegrationToken called');
        return (string)$this->scopeConfig->getValue(
            'logik_settings/general/integration_token',
            ScopeInterface::SCOPE_STORE
        );
    }
} 