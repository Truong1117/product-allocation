<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Commercers\ProductAllocation\Model\Allocation\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class IsActive
 */
class IsActive implements OptionSourceInterface
{
    /**
     * @var \Commercers\ProductAllocation\Model\Allocation
     */
    protected $cmsAllocation;

    /**
     * Constructor
     *
     * @param \Commercers\ProductAllocation\Model\Allocation $cmsAllocation
     */
    public function __construct(\Commercers\ProductAllocation\Model\Allocation $cmsAllocation)
    {
        $this->cmsAllocation = $cmsAllocation;
    }

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        $availableOptions = $this->cmsAllocation->getAvailableStatuses();
        $options = [];
        foreach ($availableOptions as $key => $value) {
            $options[] = [
                'label' => $value,
                'value' => $key,
            ];
        }
        return $options;
    }
}
