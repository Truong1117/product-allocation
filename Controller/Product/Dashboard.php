<?php

namespace Commercers\ProductAllocation\Controller\Product;

use Commercers\ProductAllocation\Model\Allocation;
use Magento\Framework\App\Action\Action;

class Dashboard extends Action{
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $pageFactory
    )
    {
        $this->_pageFactory = $pageFactory;
        return parent::__construct($context);
    }

    public function execute()
    {

    }
}
