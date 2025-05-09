<?php
declare(strict_types=1);

namespace Logik\Integration\Plugin\PageBuilder;

use Magento\PageBuilder\Model\Config\ContentType\AdditionalData\Provider;

class ContentTypeDataProvider
{
    /**
     * Add our content type to the Page Builder configuration
     *
     * @param Provider $subject
     * @param array $result
     * @return array
     */
    public function afterGetData(Provider $subject, array $result): array
    {
        $result['logik-configurator'] = [
            'label' => 'Logik Configurator',
            'menu_section' => 'general',
            'icon' => 'icon-pagebuilder-text',
            'component' => 'Logik_Integration/js/logik-configurator',
            'preview_component' => 'Logik_Integration/js/logik-configurator',
            'master_component' => 'Magento_PageBuilder/js/content-type/master',
            'form' => 'pagebuilder_contenttype_form'
        ];

        return $result;
    }
} 