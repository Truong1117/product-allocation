<?php

namespace Commercers\ProductAllocation\Block\Adminhtml\Inconsistency;

class ShowDifferenceQty extends \Magento\Backend\Block\Widget\Form\Container
{
    protected $_template = 'Commercers_ProductAllocation::inconsistency/edit/difference_qty.phtml';

    protected $_coreRegistry;
    protected $_inconsistencyFactory;
    public function __construct(
        \Commercers\ProductAllocation\Model\InconsistencyFactory $inconsistencyFactory,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Backend\Block\Widget\Context $context,
        array $data = []
    ) {
        $this->_inconsistencyFactory = $inconsistencyFactory;
        $this->_registry = $registry;
        $this->_coreRegistry = $coreRegistry;
        parent::__construct($context, $data);
    }

    /**
     * Initialize Test edit block
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_objectId = 'inconsistency_id';
        $this->_blockGroup = 'Commercers_ProductAllocation';
        $this->_controller = 'Adminhtml_Inconsistency';
        parent::_construct();
    }
    /**
     * Retrieve text for header element depending on loaded Test
     *
     * @return string
     */
    public function getHeaderText()
    {
        return __('Inconsistency Edit');
    }
    public function inconsistencyData(){
        $inconsistencyId = $this->_registry->registry('current_inconsistency_id');
        $inconsistencyModel = $this->_inconsistencyFactory->create()->load($inconsistencyId);
        return $inconsistencyModel->getData();
    }
}
