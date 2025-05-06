<?php
declare(strict_types=1);

namespace Logik\Logik\Controller\Adminhtml\SettingsPage;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Integration\Api\IntegrationServiceInterface;
use Magento\Integration\Api\AuthorizationServiceInterface;
use Magento\Integration\Api\OauthServiceInterface;
use Magento\Integration\Model\Integration;
use Zend_Log;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Cache\Manager as CacheManager;

class Save extends Action
{
    protected WriterInterface $configWriter;
    protected IntegrationServiceInterface $integrationService;
    protected AuthorizationServiceInterface $authorizationService;
    protected OauthServiceInterface $oauthService;
    protected \Zend_Log $logger;
    protected StoreManagerInterface $storeManager;
    protected CacheManager $cacheManager;

    public function __construct(
        Context $context,
        WriterInterface $configWriter,
        IntegrationServiceInterface $integrationService,
        AuthorizationServiceInterface $authorizationService,
        OauthServiceInterface $oauthService,
        StoreManagerInterface $storeManager,
        CacheManager $cacheManager
    ) {
        parent::__construct($context);
        $this->configWriter = $configWriter;
        $this->integrationService = $integrationService;
        $this->authorizationService = $authorizationService;
        $this->oauthService = $oauthService;
        $this->storeManager = $storeManager;
        $this->cacheManager = $cacheManager;
        
        // Setup logger
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/logik_settings.log');
        $this->logger = new \Zend_Log();
        $this->logger->addWriter($writer);
    }

    private function saveConfigurationValues(array $params, ?string $integrationToken = null): void
    {
        $this->logger->log('Saving configuration values...', Zend_Log::INFO);
        
        // Log the integration token
        $this->logger->log('Integration token to save: ' . ($integrationToken ?: 'none'), Zend_Log::INFO);
        
        try {
            // Save Logik URL
            $this->logger->log('Saving logik_url: ' . $params['logik_url'], Zend_Log::INFO);
            $this->configWriter->save(
                'logik_settings/general/logik_url',
                $params['logik_url'],
                'default',
                0
            );
            
            // Save Runtime Token
            $this->logger->log('Saving logik_runtime_token: ' . $params['logik_runtime_token'], Zend_Log::INFO);
            $this->configWriter->save(
                'logik_settings/general/logik_runtime_token',
                $params['logik_runtime_token'],
                'default',
                0
            );

            if ($integrationToken) {
                $this->logger->log('Saving integration_token', Zend_Log::INFO);
                $this->configWriter->save(
                    'logik_settings/general/integration_token',
                    $integrationToken,
                    'default',
                    0
                );
                $this->logger->log('Integration token saved to config', Zend_Log::INFO);
            }
            
            $this->logger->log('Config saved successfully', Zend_Log::INFO);
        } catch (\Exception $e) {
            $this->logger->log('Error saving configuration: ' . $e->getMessage(), Zend_Log::ERR);
            throw $e;
        }
    }

    private function generateTokens($integration)
    {
        try {
            $this->logger->log('Generating OAuth tokens...', Zend_Log::INFO);
            $consumerId = $integration->getConsumerId();
            
            if (!$consumerId) {
                $this->logger->log('No consumer ID found for integration', Zend_Log::ERR);
                return $integration;
            }

            $this->logger->log('Consumer ID: ' . $consumerId, Zend_Log::INFO);
            $this->oauthService->createAccessToken($consumerId);
            
            // Refresh integration data
            $integration = $this->integrationService->get($integration->getId());
            $this->logger->log('Tokens generated successfully', Zend_Log::INFO);
            return $integration;
        } catch (\Exception $e) {
            $this->logger->log('Failed to generate tokens: ' . $e->getMessage(), Zend_Log::ERR);
            return $integration;
        }
    }

    private function handleExistingIntegration($integration)
    {
        $this->logger->log('Existing integration found. Status: ' . $integration->getStatus(), Zend_Log::INFO);
        
        // Ensure the integration is active and has permissions
        if ($integration->getStatus() != Integration::STATUS_ACTIVE) {
            $integration->setStatus(Integration::STATUS_ACTIVE);
            $this->integrationService->update($integration->getData());
            $this->authorizationService->grantAllPermissions($integration->getId());
            $integration = $this->integrationService->get($integration->getId());
        }
        
        $this->logger->log('Integration ID: ' . $integration->getId(), Zend_Log::INFO);
        $this->logger->log('Integration Status: ' . $integration->getStatus(), Zend_Log::INFO);
        
        if (!$integration->getToken()) {
            $this->logger->log('No token found, generating tokens...', Zend_Log::INFO);
            $integration = $this->generateTokens($integration);
        }
        
        $this->logger->log('Integration Token: ' . ($integration->getToken() ?: 'No token'), Zend_Log::INFO);
        $this->messageManager->addSuccessMessage(
            __('Integration token saved successfully.')
        );
        
        return $integration;
    }

    private function createNewIntegration(array $params)
    {
        $this->logger->log('Integration not found, creating new one', Zend_Log::INFO);
        try {
            $integrationData = [
                'name' => 'logik',
                'email' => 'logik@example.com',
                'status' => Integration::STATUS_ACTIVE,
                'endpoint' => $params['logik_url'],
                'setup_type' => Integration::TYPE_CONFIG
            ];
            
            $integration = $this->integrationService->create($integrationData);
            $this->authorizationService->grantAllPermissions($integration->getId());
            
            // Generate OAuth tokens
            $integration = $this->generateTokens($integration);
            
            $this->logger->log('New integration created.', Zend_Log::INFO);
            $this->logger->log('Integration ID: ' . $integration->getId(), Zend_Log::INFO);
            $this->logger->log('Integration Status: ' . $integration->getStatus(), Zend_Log::INFO);
            $this->logger->log('Integration Token: ' . ($integration->getToken() ?: 'No token'), Zend_Log::INFO);
            
            $this->messageManager->addSuccessMessage(
                __('Configuration saved. New integration created with token: %1', $integration->getToken())
            );
            
            return $integration;
        } catch (\Exception $createError) {
            $this->logger->log('Failed to create integration: ' . $createError->getMessage(), Zend_Log::ERR);
            $this->messageManager->addErrorMessage(
                __('Failed to create integration token: %1', $createError->getMessage())
            );
            throw $createError;
        }
    }

    public function execute()
    {
        $params = $this->getRequest()->getParams();
        $this->logger->log('Form params: ' . print_r($params, true), Zend_Log::INFO);
        
        try {
            // Handle integration token first
            $this->logger->log('Finding integration by name: logik', Zend_Log::INFO);
            $integration = $this->integrationService->findByName('logik');
            
            if ($integration && $integration->getId()) {
                $integration = $this->handleExistingIntegration($integration);
            } else {
                $integration = $this->createNewIntegration($params);
            }
            
            // Save configuration values with the integration token
            $this->saveConfigurationValues($params, $integration->getToken());
            
            // Invalidate cache for configuration
            $this->_eventManager->dispatch('admin_system_config_changed_section_before', ['section' => 'logik_settings']);
            $this->_eventManager->dispatch('admin_system_config_changed_section_after', ['section' => 'logik_settings']);
            
            // Force a cache flush for config
            $this->_eventManager->dispatch('admin_system_config_changed');
            
            // Directly flush the config cache
            $this->logger->log('Flushing config cache directly', Zend_Log::INFO);
            $this->cacheManager->flush(['config']);
            
            $this->messageManager->addSuccessMessage(__('Configuration saved successfully.'));
            
        } catch (\Exception $e) {
            $this->logger->log('Error in save process: ' . $e->getMessage(), Zend_Log::ERR);
            $this->messageManager->addErrorMessage(__('An error occurred while saving the configuration.'));
        }

        $resultRedirect = $this->resultRedirectFactory->create();
        return $resultRedirect->setPath('*/*/', ['saved' => 1]);
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Logik_Integration::logik_settingspage');
    }
}
