<?php
/**
 * Copyright (C) EC Brands Corporation - All Rights Reserved
 * Contact Licensing@ECInternet.com for use guidelines
 */
declare(strict_types=1);

namespace ECInternet\CustomerFeatures\Controller\Account;

use Magento\Customer\Controller\Account\ForgotPassword;

/**
 * ActivateAccount controller
 */
class ActivateAccount extends ForgotPassword
{
    /**
     * Activate account page
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        /** @var \Magento\Framework\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getLayout()->getBlock('activateAccount')->setEmailValue($this->session->getForgottenEmail());

        $this->session->unsForgottenEmail();

        return $resultPage;
    }
}
