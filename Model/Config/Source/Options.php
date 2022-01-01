<?php

namespace Commercers\ProductAllocation\Model\Config\Source;

use Magento\Eav\Model\ResourceModel\Entity\Attribute\OptionFactory;
use Magento\Framework\DB\Ddl\Table;

class Options extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
    const USE_NONE = -1;
    const LABEL_NONE = 'NONE';
    protected $_customerGroup;
    public function __construct(
        \Magento\Customer\Model\ResourceModel\Group\Collection $customerGroup,
        \Magento\Backend\Block\Template\Context $context,
        array $data = []
    ) {
        $this->_customerGroup   = $customerGroup;
    }

    public function getAllOptions()
    {
        $this->_options = array(

            array(
                'value' =>(string) self::USE_NONE,
                'label' => self::LABEL_NONE
            )
        );
        foreach ($this->getGroups() as $group) {
            $this->_options[] = array(
                'value' => (string)$group->getId(),
                'label' => $group->getCode(),
            );
        }
        return $this->_options;
    }

    public function getGroups()
    {
        return $this->_customerGroup->load();
    }

}
