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
            'menu_section' => 'general',
            'component' => 'Magento_PageBuilder/js/content-type',
            'preview_component' => 'Logik_Integration/js/content-type/logik-configurator/preview',
            'master_component' => 'Magento_PageBuilder/js/content-type/master',
            'form' => 'pagebuilder_contenttype_form',
            'translate' => 'label',
            'is_system' => false,
            'additional_data' => [
                'buttons' => [
                    'settings' => [
                        'name' => 'settings',
                        'label' => __('Settings'),
                        'class' => 'pagebuilder-button-settings',
                        'sortOrder' => 20
                    ]
                ]
            ]
        ];
        return $result;
    }
} 