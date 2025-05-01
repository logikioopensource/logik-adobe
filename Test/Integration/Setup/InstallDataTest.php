<?php
declare(strict_types=1);

namespace Logik\Logik\Test\Integration\Setup;

use Magento\Catalog\Model\Product;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class InstallDataTest extends TestCase
{
    /**
     * @magentoDbIsolation enabled
     */
    public function testLogikIsConfigurableAttributeExists()
    {
        $eavConfig = Bootstrap::getObjectManager()->get(\Magento\Eav\Model\Config::class);
        $attribute = $eavConfig->getAttribute(Product::ENTITY, 'logik_is_configurable');
        
        $this->assertNotFalse($attribute);
        $this->assertEquals('logik_is_configurable', $attribute->getAttributeCode());
        $this->assertEquals('boolean', $attribute->getFrontendInput());
    }
} 