<?php
declare(strict_types=1);

namespace Logik\Integration\Controller\Adminhtml\SettingsPage;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Integration\Api\IntegrationServiceInterface;
use Magento\Integration\Api\AuthorizationServiceInterface;
use Magento\Integration\Api\OauthServiceInterface;
use Magento\Integration\Model\Integration;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Cache\Manager as CacheManager;
use Psr\Log\LoggerInterface;

class Save extends Action
{
    protected WriterInterface $configWriter;
    protected IntegrationServiceInterface $integrationService;
    protected AuthorizationServiceInterface $authorizationService;
    protected OauthServiceInterface $oauthService;
    protected LoggerInterface $logger;
    protected StoreManagerInterface $storeManager;
    protected CacheManager $cacheManager;

    public function __construct(
        Context $context,
        WriterInterface $configWriter,
        IntegrationServiceInterface $integrationService,
        AuthorizationServiceInterface $authorizationService,
        OauthServiceInterface $oauthService,
        StoreManagerInterface $storeManager,
        CacheManager $cacheManager,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->configWriter = $configWriter;
        $this->integrationService = $integrationService;
        $this->authorizationService = $authorizationService;
        $this->oauthService = $oauthService;
        $this->storeManager = $storeManager;
        $this->cacheManager = $cacheManager;
        $this->logger = $logger;
    }

    private function saveConfigurationValues(array $params, ?string $integrationToken = null): void
    {
        $this->logger->info('Saving configuration values...');
        
        // Log the integration token
        $this->logger->info('Integration token to save: ' . ($integrationToken ? '[TOKEN_PRESENT]' : 'none'));
        
        try {
            // Save Logik URL
            $this->logger->info('Saving logik_url: ' . $params['logik_url']);
            $this->configWriter->save(
                'logik_settings/general/logik_url',
                $params['logik_url'],
                'default',
                0
            );
            
            // Save Runtime Token
            $this->logger->info('Saving logik_runtime_token: ' . $params['logik_runtime_token']);
            $this->configWriter->save(
                'logik_settings/general/logik_runtime_token',
                $params['logik_runtime_token'],
                'default',
                0
            );

            if ($integrationToken) {
                $this->logger->info('Saving integration_token');
                $this->configWriter->save(
                    'logik_settings/general/integration_token',
                    $integrationToken,
                    'default',
                    0
                );
                $this->logger->info('Integration token saved to config');
            }
            
            $this->logger->info('Config saved successfully');
        } catch (\Exception $e) {
            $this->logger->error('Error saving configuration: ' . $e->getMessage());
            throw $e;
        }
    }

    private function generateTokens($integration)
    {
        try {
            $this->logger->info('Generating OAuth tokens...');
            $consumerId = $integration->getConsumerId();
            
            if (!$consumerId) {
                $this->logger->error('No consumer ID found for integration');
                return $integration;
            }

            $this->logger->info('Consumer ID: ' . $consumerId);
            $this->oauthService->createAccessToken($consumerId);
            
            // Refresh integration data
            $integration = $this->integrationService->get($integration->getId());
            $this->logger->info('Tokens generated successfully');
            return $integration;
        } catch (\Exception $e) {
            $this->logger->error('Failed to generate tokens: ' . $e->getMessage());
            return $integration;
        }
    }

    private function handleExistingIntegration($integration)
    {
        $this->logger->info('Existing integration found. Status: ' . $integration->getStatus());
        
        // Ensure the integration is active and has permissions
        if ($integration->getStatus() != Integration::STATUS_ACTIVE) {
            $integration->setStatus(Integration::STATUS_ACTIVE);
            $this->integrationService->update($integration->getData());
            // Grant required permissions
            $this->authorizationService->grantPermissions($integration->getId(), [
                'Logik_Integration::add_to_cart',
                'Magento_Catalog::catalog',
                'Magento_Catalog::products'
            ]);
            $integration = $this->integrationService->get($integration->getId());
        }
        
        $this->logger->info('Integration ID: ' . $integration->getId());
        $this->logger->info('Integration Status: ' . $integration->getStatus());
        
        if (!$integration->getToken()) {
            $this->logger->info('No token found, generating tokens...');
            $integration = $this->generateTokens($integration);
        }
        
        // Sanitize token logging - only log if token exists
        $this->logger->info('Integration Token: ' . ($integration->getToken() ? '[TOKEN_PRESENT]' : 'No token'));
        $this->messageManager->addSuccessMessage(
            __('Integration token saved successfully.')
        );
        
        return $integration;
    }

    private function createNewIntegration(array $params)
    {
        $this->logger->info('Integration not found, creating new one');
        try {
            $integrationData = [
                'name' => 'logik',
                'email' => 'logik@example.com',
                'status' => Integration::STATUS_ACTIVE,
                'endpoint' => $params['logik_url'],
                'setup_type' => Integration::TYPE_CONFIG
            ];
            
            $integration = $this->integrationService->create($integrationData);
            // Grant required permissions
            $this->authorizationService->grantPermissions($integration->getId(), [
                'Logik_Integration::add_to_cart',
                'Magento_Catalog::catalog',
                'Magento_Catalog::products'
            ]);
            
            // Generate OAuth tokens
            $integration = $this->generateTokens($integration);
            
            $this->logger->info('New integration created.');
            $this->logger->info('Integration ID: ' . $integration->getId());
            $this->logger->info('Integration Status: ' . $integration->getStatus());
            $this->logger->info('Integration Token: ' . ($integration->getToken() ? '[TOKEN_PRESENT]' : 'No token'));
            
            $this->messageManager->addSuccessMessage(
                __('Configuration saved. New integration created with token: %1', $integration->getToken())
            );
            
            return $integration;
        } catch (\Exception $createError) {
            $this->logger->error('Failed to create integration: ' . $createError->getMessage());
            $this->messageManager->addErrorMessage(
                __('Failed to create integration token: %1', $createError->getMessage())
            );
            throw $createError;
        }
    }

    public function execute()
    {
        $params = $this->getRequest()->getParams();
        $this->logger->info('Form params: ' . print_r($params, true));
        
        try {
            // Handle integration token first
            $this->logger->info('Finding integration by name: logik');
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
            $this->logger->info('Flushing config cache directly');
            $this->cacheManager->flush(['config']);
            
            $this->messageManager->addSuccessMessage(__('Configuration saved successfully.'));
            
        } catch (\Exception $e) {
            $this->logger->error('Error in save process: ' . $e->getMessage());
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
