<?php

namespace Commercers\ProductAllocation\Block\Adminhtml\Manage\Edit;
use Commercers\ProductAllocation\Model\Allocation;
use \Magento\Backend\Block\Widget\Form\Generic;

class Form extends Generic
{

    /**
     * @var \Magento\Store\Model\System\Store
     */
    protected $_systemStore;

    protected $_allocationFactory;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Store\Model\System\Store $systemStore
     * @param array $data
     */
    public function __construct(
        \Commercers\ProductAllocation\Model\AllocationFactory $allocationFactory,
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Store\Model\System\Store $systemStore,
        array $data = []
    ) {
        $this->_allocationFactory = $allocationFactory;
        $this->_systemStore = $systemStore;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * Init form
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('product_allocation_form');
        $this->setTitle(__('Allocation Information'));
    }

    /**
     * Prepare form
     *
     * @return $this
     */
    protected function _prepareForm()
    {
//        /** @var \Commercers\ProductAllocation\Model\Allocation $model */
//        $model = $this->_coreRegistry->registry('product_allocation');
        $model = $this->_allocationFactory->create()->getCollection();
        var_dump($model->getData());exit;
        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create(
            ['data' => ['id' => 'edit_form', 'action' => $this->getData('action'), 'method' => 'post']]
        );

        $form->setHtmlIdPrefix('product_allocation_');

        $fieldset = $form->addFieldset(
            'base_fieldset',
            ['legend' => __('General Information'), 'class' => 'fieldset-wide']
        );

        if ($model->getAllocationId()) {
            $fieldset->addField('allocation_id', 'hidden', ['name' => 'allocation_id']);
        }

//        $fieldset->addField(
//            'name',
//            'text',
//            ['name' => 'name', 'label' => __('Department Name'), 'title' => __('Department Name'), 'required' => true]
//        );
//
//        $fieldset->addField(
//            'description',
//            'textarea',
//            ['name' => 'description', 'label' => __('Department Description'), 'title' => __('Department Description'), 'required' => true]
//        );

        $form->setValues($model->getData());
        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }
}
