<?php

namespace Commercers\ProductAllocation\Block\Adminhtml\ImportData;

class Index extends \Magento\Backend\Block\Widget\Form\Container
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /**
     * constructor
     *
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Backend\Block\Widget\Context $context,
        array $data = []
    ) {

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
        $this->_objectId = 'allocation_id';
        $this->_blockGroup = 'Commercers_ProductAllocation';
        $this->_controller = 'Adminhtml_ImportData';
        parent::_construct();
        $this->buttonList->remove('save');
//        $this->buttonList->remove('back');
        $this->buttonList->remove('reset');
        $this->buttonList->add(
            'save-and-continue',
            [
                'label' => __('Import'),
                'class' => 'save primary',
                'data_attribute' => [
                    'mage-init' => [
                        'button' => [
                            'event' => 'saveAndContinueEdit',
                            'target' => '#edit_form'
                        ]
                    ]
                ]
            ],
            -100
        );
        $this->buttonList->remove('delete');
    }
    /**
     * Retrieve text for header element depending on loaded Test
     *
     * @return string
     */
    public function getHeaderText()
    {
        return __('Import Product Allocation');
    }
}
