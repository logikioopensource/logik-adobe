<?php
namespace Logik\Integration\Plugin\PageBuilder;

class AdditionalDataProvider
{
    public function afterGetData(
        \Magento\PageBuilder\Model\Config\ContentType\AdditionalData\Provider $subject,
        $result
    ) {
        $result['logik_configurator'] = [
            'label' => __('Logik Configurator'),
            'icon' => 'icon-pagebuilder-text',
            'menu_section' => 'general'
        ];
        return $result;
    }
} 