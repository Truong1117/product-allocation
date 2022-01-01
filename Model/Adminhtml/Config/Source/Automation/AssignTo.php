<?php

namespace Commercers\ProductAllocation\Model\Adminhtml\Config\Source\Automation;

class AssignTo extends \Magento\Eav\Model\Entity\Attribute\Backend\ArrayBackend
{
    protected $_options = null;
    public function toOptionArray()
    {
        $this->_options = array();
        $this->_options = array(
            array(
                'label' => 'Individual',
                'value' => 1
            ),
            array(
                'label' => 'Customer Group',
                'value' => 2
            )
        );
        return $this->_options;
    }
    public function getAllOptions()
    {
        return $this->toOptionArray();
    }
}
