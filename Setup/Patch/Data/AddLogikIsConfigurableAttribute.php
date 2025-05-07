<?php
declare(strict_types=1);

namespace Logik\Integration\Setup\Patch\Data;

use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use Magento\Catalog\Model\Product;
use Psr\Log\LoggerInterface;

class AddLogikIsConfigurableAttribute implements DataPatchInterface, PatchRevertableInterface
{
    private ModuleDataSetupInterface $moduleDataSetup;
    private EavSetupFactory $eavSetupFactory;
    private LoggerInterface $logger;

    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        EavSetupFactory $eavSetupFactory,
        LoggerInterface $logger
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->eavSetupFactory = $eavSetupFactory;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        try {
            $this->moduleDataSetup->startSetup();
            
            /** @var EavSetup $eavSetup */
            $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

            $this->logger->info('Installing logik_is_configurable attribute');

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

            $this->logger->info('Successfully installed logik_is_configurable attribute');
            $this->moduleDataSetup->endSetup();
            
        } catch (\Exception $e) {
            $this->logger->error('Error installing logik_is_configurable attribute: ' . $e->getMessage());
            throw $e;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function revert()
    {
        try {
            $this->moduleDataSetup->startSetup();
            
            /** @var EavSetup $eavSetup */
            $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

            $this->logger->info('Removing logik_is_configurable attribute');

            $eavSetup->removeAttribute(
                Product::ENTITY,
                'logik_is_configurable'
            );

            $this->logger->info('Successfully removed logik_is_configurable attribute');
            $this->moduleDataSetup->endSetup();
            
        } catch (\Exception $e) {
            $this->logger->error('Error removing logik_is_configurable attribute: ' . $e->getMessage());
            throw $e;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }
} 