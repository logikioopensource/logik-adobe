<?php
declare(strict_types=1);

namespace Logik\Integration\Controller\Config;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;

class Get extends Action
{
    protected JsonFactory $resultJsonFactory;
    protected ScopeConfigInterface $scopeConfig;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        ScopeConfigInterface $scopeConfig
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->scopeConfig = $scopeConfig;
    }

    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        
        try {
            $config = [
                'runtime_token' => $this->scopeConfig->getValue('logik_settings/general/logik_runtime_token'),
                'tenant_api_url' => $this->scopeConfig->getValue('logik_settings/general/logik_url'),
                'integration_token' => $this->scopeConfig->getValue('logik_settings/general/integration_token')
            ];
            
            return $result->setData($config);
        } catch (\Exception $e) {
            return $result->setData(['error' => $e->getMessage()]);
        }
    }
} 