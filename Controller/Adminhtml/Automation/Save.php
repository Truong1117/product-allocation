<?php
namespace Commercers\ProductAllocation\Controller\Adminhtml\Automation;

use Commercers\ProductAllocation\Helper\History;

class Save extends \Magento\Backend\App\Action
{
    public function __construct(
        \Magento\Backend\App\Action\Context $context
    )
    {
        parent::__construct($context);
    }

    public function execute()
    {
        $params = $this->getRequest()->getParams();
        if($params["is_enable"] == 0){
            return $this->_redirect('productallocation/automation/index');
        }
        var_dump($params);exit;
        try {

        } catch (Exception $e) {
            //echo $e->getMessage();exit;
            $this->_redirect('*/*/listing');
        }
    }
}
