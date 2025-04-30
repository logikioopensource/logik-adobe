<?php
declare(strict_types=1);

namespace Logik\Logik\Block\Adminhtml\Form\Field;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class IntegrationToken extends Field
{
    protected function _getElementHtml(AbstractElement $element)
    {
        $html = '<div class="integration-token-wrapper">';
        $html .= '<input type="password" id="' . $element->getHtmlId() . '" 
                    name="' . $element->getName() . '"
                    class="integration-token-input" 
                    value="' . $element->getEscapedValue() . '" 
                    readonly="readonly">';
        $html .= '<button type="button" 
                    class="action-default scalable toggle-token" 
                    data-mage-init=\'{"Logik_Logik/js/toggle-token": {}}\'>
                    <span>Show Token</span>
                </button>';
        $html .= '</div>';

        return $html;
    }

    protected function _renderScopeLabel(AbstractElement $element)
    {
        return '';
    }
} 