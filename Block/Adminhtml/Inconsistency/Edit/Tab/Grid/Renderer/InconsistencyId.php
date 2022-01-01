<?php
namespace Commercers\ProductAllocation\Block\Adminhtml\Inconsistency\Edit\Tab\Grid\Renderer;

class InconsistencyId extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
    /**
     * Renders grid column
     * @param \Magento\Framework\DataObject $row
     * @return string
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        $inconsistencyId = $this->getRequest()->getParam('inconsistency_id');
        return '<input type="hidden" name="inconsistency_current_id" value="'.$inconsistencyId.'" class="input-text">';
    }
}