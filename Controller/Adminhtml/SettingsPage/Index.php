<?php
namespace Logik\Logik\Controller\Adminhtml\SettingsPage;

/**
 * Copyright [first year code created] Adobe
 * All rights reserved.
 */


use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action implements HttpGetActionInterface
{
    const MENU_ID = 'Logik_Logik::logik_settingspage';

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * Index constructor.
     *
     * @param Context $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory
    ) {
        parent::__construct($context);

        $this->resultPageFactory = $resultPageFactory;
    }

    /**
     * Load the page defined in view/adminhtml/layout/logiksettings_settingspage_index.xml
     *
     * @return Page
     */
    public function execute()
    {
        // Add debugging
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/logik_settings.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info('Settings page controller executed');

        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu(static::MENU_ID);
        $resultPage->getConfig()->getTitle()->prepend(__('Logik Settings'));

        return $resultPage;
    }
}
