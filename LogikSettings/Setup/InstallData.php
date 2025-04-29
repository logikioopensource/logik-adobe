<?php
declare(strict_types=1);

namespace Logik\LogikSettings\Setup;

use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Catalog\Model\Product;
use Zend_Log;

class InstallData implements InstallDataInterface
{
    private EavSetupFactory $eavSetupFactory;
    private \Zend_Log $logger;

    public function __construct(
        EavSetupFactory $eavSetupFactory
    ) {
        $this->eavSetupFactory = $eavSetupFactory;
        
        // Setup logger to write to the same file as the Save controller
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/logik_settings.log');
        $this->logger = new \Zend_Log();
        $this->logger->addWriter($writer);
    }

    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        try {
            $setup->startSetup();
            
            /** @var EavSetup $eavSetup */
            $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);

            $this->logger->log('Installing logik_is_configurable attribute', Zend_Log::INFO);

            $eavSetup->addAttribute(
                Product::ENTITY,
                'logik_is_configurable',
                [
                    'type' => 'int',
                    'label' => 'Logik Is Configurable',
                    'input' => 'boolean',
                    'source' => \Magento\Eav\Model\Entity\Attribute\Source\Boolean::class,
                    'required' => false,
                    'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                    'visible' => true,
                    'user_defined' => true,
                    'default' => '0',
                    'searchable' => false,
                    'filterable' => false,
                    'comparable' => false,
                    'visible_on_front' => false,
                    'used_in_product_listing' => true,
                    'unique' => false,
                    'apply_to' => '',
                    'group' => 'General',
                    'sort_order' => 50
                ]
            );

            $this->logger->log('Successfully installed logik_is_configurable attribute', Zend_Log::INFO);
            $setup->endSetup();
            
        } catch (\Exception $e) {
            $this->logger->log('Error installing logik_is_configurable attribute: ' . $e->getMessage(), Zend_Log::ERR);
            throw $e;
        }
    }
} 