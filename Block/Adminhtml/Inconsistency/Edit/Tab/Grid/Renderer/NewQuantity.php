<?php
namespace Commercers\ProductAllocation\Block\Adminhtml\Inconsistency\Edit\Tab\Grid\Renderer;

class NewQuantity extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
    /**
     * Renders grid column
     * @param \Magento\Framework\DataObject $row
     * @return string
     */
    const VALUE_GROUP_QTY_NOT_ASSIGNED = 1;
    public function render(\Magento\Framework\DataObject $row)
    {
        $inconsistencyId = $this->getRequest()->getParam('inconsistency_id');
        $groupQtyNotAssigned = $row->getGroupQtyNotAssigned();
        $html = '';
        if($groupQtyNotAssigned == self::VALUE_GROUP_QTY_NOT_ASSIGNED){
            $html = '<input type="number" readonly min="0" name="new_qty['.$row->getAllocationId().']" value="" class="input-text">
        <input type="hidden" name="inconsistency_id" value="'.$inconsistencyId.'" class="input-text">
        ';
        }else{
            $html = '<input type="number" min="0" name="new_qty['.$row->getAllocationId().']" value="" class="input-text">
        <input type="hidden" name="inconsistency_id" value="'.$inconsistencyId.'" class="input-text">
        ';
        }
        return $html;
    }
}