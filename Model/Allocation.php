<?php

namespace Commercers\ProductAllocation\Model;
use Magento\Framework\DataObject\Factory as ObjectFactory;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ResourceModel\ProductFactory as ProductResourceFactory;
use Magento\Framework\Stdlib\DateTime\DateTime;

class Allocation extends \Magento\Framework\Model\AbstractModel implements \Magento\Framework\DataObject\IdentityInterface
{
    const VALUE_QTY_ASSIGNED = 0;
    const VALUE_QTY_NOT_ASSIGNED = 1;
    /**
     * @var ObjectFactory
     */
    private $objectFactory;
    /**#@+
     * Rule's Statuses
     */
    const STATUS_ENABLED = 1;
    const STATUS_DISABLED = 0;
    /**#@-*/

    const CACHE_TAG = 'commercers_product_allocation';

    protected $_cacheTag = 'commercers_product_allocation';

    protected $_eventPrefix = 'commercers_product_allocation';

    /**
     * @var ProductFactory
     */
    protected $_productFactory;

    /**
     * @var ProductResourceFactory
     */
    protected $_productResourceFactory;
    protected $_date;
    protected $resourceConnectionFactory;
    protected $_allocationModelFactory;
    protected $productRepository;
    protected $_customerModel;
    protected $_allocationHelperFactory;
    protected $_localeFormat;
    public function __construct(
        \Magento\Framework\Locale\FormatInterface $localeFormat,
        \Commercers\ProductAllocation\Helper\AllocationFactory $allocationHelperFactory,
        ObjectFactory $objectFactory,
        \Magento\Customer\Model\Customer $customerModel,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Commercers\ProductAllocation\Model\AllocationFactory $allocationModelFactory,
        \Magento\Framework\App\ResourceConnectionFactory $resourceConnectionFactory,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        ProductFactory $productFactory,
        ProductResourceFactory $productResourceFactory,
        DateTime $date,
        array $data = []
    ) {
        $this->_localeFormat = $localeFormat;
        $this->_allocationHelperFactory = $allocationHelperFactory;
        $this->objectFactory = $objectFactory;
        $this->_customerModel = $customerModel;
        $this->productRepository = $productRepository;
        $this->_allocationModelFactory = $allocationModelFactory;
        $this->resourceConnectionFactory = $resourceConnectionFactory;
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
        $this->_init('Commercers\ProductAllocation\Model\ResourceModel\Allocation');
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

    public function getDataAllocation($sku,$email,$websiteId){
        $connection = $this->resourceConnectionFactory->create();
        $conn = $connection->getConnection();
        $select = $conn->select()
            ->from(
                ['o' => 'commercers_product_allocation']
            )
            ->where('o.sku=?', $sku)
            ->where('o.user=?',$email)
            ->where('o.website_id=?',$websiteId);
        $id = $conn->fetchOne($select);
        $productAllocationModel = $this->_allocationModelFactory->create();
        if($id){
            $productAllocationModel->load($id);
        }else{
            $productAllocationModel->setData([]);
        }
        return $productAllocationModel;
    }
    public function isValidForImport() {
        $errors = [];
        $productId = $this->productRepository->get($this->getSku())->getId();
        // validate email, quantity and if sku exists
        if (!$productId) {
            $errors[] = __( 'Product With SKU "%s" Doesn\'t Exist.', $this->getSku());
        }
        $customerData = $this->_customerModel->getCollection()
            ->addFieldToFilter('email', $this->getEmail());
        if(!count($customerData)) {
            $errors[] = __( 'Email "%s" Is Not Valid.', $this->getEmail());
        }
        if (!is_numeric($this->getQty())) {
            $errors[] = __('Quantity "%s" Is Not Valid.',$this->getQty());
        }
        if (empty($errors)) {
            return true;
        }
        return $errors;
    }

    /**
     * Checking quote item quantity
     *
     * @param mixed $qty
     *        	quantity of this item (item qty x parent item qty)
     * @param mixed $summaryQty
     *        	quantity of this product in whole shopping cart which should be checked for stock availability
     * @param mixed $origQty
     *        	original qty of item (not multiplied on parent item qty)
     * @return Varien_Object
     */
    public function checkQuoteItemQty($qty,$summaryQty,$origQty=0,$name='',$websiteId=null) {
        $result = $this->objectFactory->create();
        $result->setHasError(false);

        $allocationHelper = $this->_allocationHelperFactory->create();
        if(!$allocationHelper->isEnabled(null)){
            return $result;
        }
        if (!is_numeric($qty)){
            $qty = $this->_localeFormat->getNumber($qty);
        }
        $qty = intval($qty);
        if (!is_numeric($qty)) {
            $qty = $this->_localeFormat->getNumber($qty);
        }
        $origQty = intval($origQty);
        if (!$this->checkQty($summaryQty)) {
            $message = __ ('The requested quantity for "%s" is not available. Your allocation limit for purchasing this product is '.(int)$this->getQty().'.');
            $result->setHasError(true )->setMessage($message ->setQuoteMessage($message)->setQuoteMessageIndex('qty'));
        }

        return $result;
    }

    /**
     * Check quantity
     *
     * @param decimal $qty
     * @return bool
     */
    public function checkQty($qty) {
        if ($this->getQty() - $qty < 0) {
            return false;
        }
        return true;
    }

    public function increaseQty($qtyToIncrease) {
        $oldQty = $this->getQty();
        if ($oldQty + $qtyToIncrease > 0) {
            $this->setQty($oldQty + $qtyToIncrease);
        } else {
            $this->setQty(0);
        }
        return $this;
    }

}
