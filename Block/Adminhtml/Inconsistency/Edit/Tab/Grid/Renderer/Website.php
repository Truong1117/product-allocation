<?php
namespace Commercers\ProductAllocation\Block\Adminhtml\Inconsistency\Edit\Tab\Grid\Renderer;

class Website extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
   protected $_websiteModel;
    public function __construct(
        \Magento\Store\Model\Website $websiteModel
    ){
        $this->_websiteModel = $websiteModel;
    }
    public function render(\Magento\Framework\DataObject $row)
    {
        $websiteId = $row->getWebsiteId();
        return $this->getWebsiteName($websiteId);
    }
    protected function getWebsiteName($websiteId){
        $collection = $this->_websiteModel->load($websiteId,'website_id');
        return $collection->getName();
    }
}