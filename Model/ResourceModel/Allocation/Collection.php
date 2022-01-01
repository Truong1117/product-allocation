<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Commercers\ProductAllocation\Model\ResourceModel\Allocation;

use Magento\Cms\Api\Data\PageInterface;
use \Magento\Cms\Model\ResourceModel\AbstractCollection;

/**
 * CMS page collection
 */
class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'allocation_id';

    /**
     * Load data for preview flag
     *
     * @var bool
     */
    protected $_previewFlag;

    /**
     * Event prefix
     *
     * @var string
     */
    protected $_eventPrefix = 'commercers_product_allocation_collection';

    /**
     * Event object
     *
     * @var string
     */
    protected $_eventObject = 'product_allocation_collection';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Commercers\ProductAllocation\Model\Allocation::class, \Commercers\ProductAllocation\Model\ResourceModel\Allocation::class);
        $this->_map['fields']['allocation_id'] = 'main_table.allocation_id';
        $this->_map['fields']['store'] = 'store_table.store_id';
    }

    /**
     * Set first store flag
     *
     * @param bool $flag
     * @return $this
     */
    // public function setFirstStoreFlag($flag = false)
    // {
    //     $this->_previewFlag = $flag;
    //     return $this;
    // }

    /**
     * Add filter by store
     *
     * @param int|array|\Magento\Store\Model\Store $store
     * @param bool $withAdmin
     * @return $this
     */
    public function addStoreFilter($store, $withAdmin = true)
    {
        if (!$this->getFlag('store_filter_added')) {
            $this->performAddStoreFilter($store, $withAdmin);
            $this->setFlag('store_filter_added', true);
        }

        return $this;
    }

    /**
     * Perform operations after collection load
     *
     * @return $this
     */
    // protected function _afterLoad()
    // {
    //     $entityMetadata = $this->metadataPool->getMetadata(PageInterface::class);
    //     $this->performAfterLoad('commercers_orderapproval_store', $entityMetadata->getLinkField());
    //     $this->_previewFlag = false;

    //     return parent::_afterLoad();
    // }

    /**
     * Perform operations before rendering filters
     *
     * @return void
     */
    // protected function _renderFiltersBefore()
    // {
    //     $entityMetadata = $this->metadataPool->getMetadata(PageInterface::class);
    //     $this->joinStoreRelationTable('commercers_orderapproval_store', $entityMetadata->getLinkField());
    // }
}
