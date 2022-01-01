<?php
namespace Commercers\ProductAllocation\Model\ResourceModel\Allocation;

use Commercers\ProductAllocation\Model\ResourceModel\Allocation\CollectionFactory;
use Commercers\ProductAllocation\Model\Allocation;

class DataProvider extends \Magento\Ui\DataProvider\AbstractDataProvider
{
    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $allocationCollectionFactory
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $allocationCollectionFactory,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $allocationCollectionFactory->create();
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }

        $items = $this->collection->getItems();
        $this->loadedData = array();
        /** @var Allocation $allocation */
        foreach ($items as $allocation) {
            // our fieldset is called "contact" or this table so that magento can find its datas:
            $this->loadedData[$allocation->getAllocationId()]['allocation'] = $allocation->getData();
        }

        return $this->loadedData;
    }
}
