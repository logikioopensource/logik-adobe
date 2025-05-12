<?php
declare(strict_types=1);

namespace Logik\Integration\Block\Adminhtml;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Logik\Integration\ViewModel\Adminhtml\SettingsViewModel;

class SettingsPage extends Template
{
    private SettingsViewModel $viewModel;

    public function __construct(
        Context $context,
        SettingsViewModel $viewModel,
        array $data = []
    ) {
        $this->viewModel = $viewModel;
        parent::__construct($context, $data);
    }

    public function getViewModel(): SettingsViewModel
    {
        return $this->viewModel;
    }
}
