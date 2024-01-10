<?php
/**
 * Copyright (C) EC Brands Corporation - All Rights Reserved
 * Contact Licensing@ECInternet.com for use guidelines
 */
declare(strict_types=1);

namespace ECInternet\CustomerFeatures\Block\Customer\Account;

use Magento\Customer\Block\Account\Forgotpassword;

/**
 * Activateaccount block
 */
class Activateaccount extends Forgotpassword
{
    /**
     * Get Activation note
     *
     * @return string
     */
    public function getActivationNote()
    {
        return 'Please enter an email address below to receive an account activation link.';
    }
}
