<?php
namespace Commercers\ProductAllocation\Block\Adminhtml\Inconsistency\Edit;

class Tabs extends \Magento\Backend\Block\Widget\Tabs
{
    /**
     * constructor
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('productallocation_inconsistency_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(__('Inconsistency Edit'));
    }
}
