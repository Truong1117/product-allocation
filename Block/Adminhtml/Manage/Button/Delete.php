<?php

namespace Commercers\ProductAllocation\Block\Adminhtml\Manage\Button;
use Magento\Catalog\Block\Adminhtml\Product\Edit\Button\Generic;
/**
 * Class Back
 */
class Delete extends Generic
{
    /**
     * @return array
     */
    public function getButtonData()
    {
        return [
            'label' => __('Delete'),
            'on_click' => sprintf("location.href = '%s';", $this->getUrl('productallocation/manage/delete')),
            'class' => 'delete',
            'sort_order' => 15
        ];
    }
}
