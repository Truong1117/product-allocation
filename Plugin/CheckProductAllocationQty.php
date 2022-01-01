<?php
namespace Commercers\ProductAllocation\Plugin;
use Magento\Framework\Message\ManagerInterface;
use Magento\Checkout\Model\Session;
use Magento\Checkout\Model\Cart;

class CheckProductAllocationQty
{
    /**
     * @var ManagerInterface
     */
    protected $_messageManager;

    /**
     * @var \Magento\Quote\Model\Quote
     */
    protected $quote;

    /**
     * Plugin constructor.
     *
     * @param \Magento\Checkout\Model\Session $checkoutSession
     */
    public function __construct(
        \Commercers\ProductAllocation\Helper\AllocationFactory $allocationHelperFactory,
        \Commercers\ProductAllocation\Model\AllocationFactory $allocationModelFactory,
        \Commercers\ProductAllocation\Helper\DataFactory $dataHelperFactory,
        \Magento\Customer\Model\SessionFactory $customerSessionFactory,
        Session $checkoutSession,
        ManagerInterface $messageManager
    ) {
        $this->_allocationHelperFactory = $allocationHelperFactory;
        $this->_allocationModelFactory = $allocationModelFactory;
        $this->_dataHelperFactory = $dataHelperFactory;
        $this->customerSessionFactory = $customerSessionFactory;
        $this->quote = $checkoutSession->getQuote();
        $this->_messageManager = $messageManager;
    }

    /**
     * @param \Magento\Checkout\Model\Cart $subject
     * @param $data
     * @return array
     */
    public function beforeUpdateItems(Cart $subject,$data)
    {
        $quote = $subject->getQuote();
//        $matchedProductId = 27;
//        $allowedCartQty  = 2;

        $customer = $this->customerSessionFactory->create();
        $emailCustomer = $customer->getCustomer()->getEmail();
        $dataHelper = $this->_dataHelperFactory->create();
        $websiteId = $dataHelper->getCurrentWebsiteId();
        foreach($data as $key => $value){
            $item = $quote->getItemById($key);
            $itemQty= $value['qty'];
            $helperAllocation = $this->_allocationHelperFactory->create();
            if (!intval($helperAllocation->isEnabled())) {
                return [$data];
            }
            $allocationModel = $this->_allocationModelFactory->create();
            $productAllocation = $allocationModel->getDataAllocation($item['sku'], $emailCustomer, $websiteId);
            $qtyProductAllocation = $productAllocation->getQty();
            if($productAllocation->getData() && intval($itemQty) > intval($qtyProductAllocation)){
                $this->_messageManager->addErrorMessage(__('Product %1 Not Enough Quantity Allocation. Your current allocation is %2',
                    $item->getName(),
                    $qtyProductAllocation
                ));
    //            $this->_messageManager->addNoticeMessage($item->getName().' cannot be ordered in requested quantity.');
                    $data[$key]['qty']=$item['qty'];
            }
        }
        return [$data];
    }
}