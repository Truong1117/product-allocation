<?php

namespace Commercers\ProductAllocation\Model;

use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ResourceModel\ProductFactory as ProductResourceFactory;
use Magento\Framework\Stdlib\DateTime\DateTime;

class Inconsistency extends \Magento\Framework\Model\AbstractModel implements \Magento\Framework\DataObject\IdentityInterface
{

    /**#@+
     * Rule's Statuses
     */
    const STATUS_ENABLED = 1;
    const STATUS_DISABLED = 0;
    /**#@-*/

    const CACHE_TAG = 'commercers_product_allocation_inconsistency';

    protected $_cacheTag = 'commercers_product_allocation_inconsistency';

    protected $_eventPrefix = 'commercers_product_allocation_inconsistency';

    /**
     * @var ProductFactory
     */
    protected $_productFactory;

    /**
     * @var ProductResourceFactory
     */
    protected $_productResourceFactory;
    protected $_date;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        ProductFactory $productFactory,
        ProductResourceFactory $productResourceFactory,
        DateTime $date,
        array $data = []
    ) {
        $this->_productFactory = $productFactory;
        $this->_productResourceFactory = $productResourceFactory;
        $this->_date = $date;
        parent::__construct(
            $context,
            $registry,
            $resource,
            $resourceCollection
        );
    }

    protected function _construct()
    {
        $this->_init('Commercers\ProductAllocation\Model\ResourceModel\Inconsistency');
    }

    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    public function getDefaultValues()
    {
        $values = [];

        return $values;
    }

    /**
     * Prepare rule's statuses, available event cms_rule_get_available_statuses to order_approval statuses.
     *
     * @return array
     */
    public function getAvailableStatuses()
    {
        return [self::STATUS_ENABLED => __('Enabled'), self::STATUS_DISABLED => __('Disabled')];
    }
}
