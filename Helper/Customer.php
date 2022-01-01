<?php
namespace Commercers\ProductAllocation\Helper;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
class Customer extends AbstractHelper
{
    protected $_customerSession;
    public function __construct(
        \Magento\Customer\Model\Session $customerSession
    ) {
        $this->_customerSession = $customerSession;
    }
    public function getCustomerEmail()
    {
        $customerData = $this->_customerSession->getCustomer();
        return $customerData->getEmail();
    }
}

