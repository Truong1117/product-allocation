<?php
namespace Commercers\ProductAllocation\Block\Adminhtml\Inconsistency\Edit\Tab\Grid\Renderer;

class CustomerGroup extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
    protected $_customerGroupCollection;
    public function __construct(
        \Magento\Customer\Model\Group $customerGroupCollection
    ){
        $this->_customerGroupCollection = $customerGroupCollection;
    }
    public function render(\Magento\Framework\DataObject $row)
    {
        $userGroupId = $row->getUserGroup();
        return $this->getCustomerGroupName($userGroupId);
    }
    protected function getCustomerGroupName($userGroupId){
        $groupCollection = $this->_customerGroupCollection->load($userGroupId);
        return $groupCollection->getCustomerGroupCode();
    }
}