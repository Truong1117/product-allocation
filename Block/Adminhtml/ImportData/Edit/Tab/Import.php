<?php

namespace Commercers\ProductAllocation\Block\Adminhtml\ImportData\Edit\Tab;

class Import extends \Magento\Backend\Block\Widget\Form\Generic implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    protected function _prepareForm()
    {
        $form = $this->_formFactory->create();
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        $fieldset = $form->addFieldset(
            'base_fieldset',
            [
                'legend' => __('Import Product Allocation'),
                'class'  => 'fieldset-wide'
            ]
        );
        $fieldset->addField(
            'file',
            'file',
            [
                'name'  => 'file',
                'label' => __('Upload File'),
                'title' => __('Upload File'),
                'required' => true,
                'note' => __('Update Data By file CSV.') . '&nbsp;<span>Download sample file</span><a href="'.$this->getUrl('productallocation/importdata/examplefile', $params=null).'"> [Here]</a>'
            ]
        );

        // $fieldset->addField('example_file', 'text', [
        //     'name' => 'example_file',
        //     'label' => __('Example File'),
        //     'title' => __('Example File'),
        //     'required' => false,
        // ])->setAfterElementHtml('
        //     <div class="field-tooltip toggle">
        //         <div class="field-tooltip-content">
        //              <span>Download sample file</span><a href="'.$this->getUrl('productallocation/importdata/examplefile', $params=null).'"> [Here]</a>

        //         </div>
        //     </div>
        // ');
        $fieldset->addField(
            'option_import',
            'select',
            [
                'values' => ['0' => __('Add/Update'), '1' => __('Replace')],
                'name' => 'option_import',
                'label' => __('Option Import'),
                'title' => __('Option Import'),
                'class' => 'option_import'
            ]
        );
        $fieldset->addField(
            'website_id',
            'select',
            [
                'values' => [1 => __('Main Website'), 2 => __('Allocation Website')],
                'name' => 'option_website',
                'label' => __('Website'),
                'title' => __('Website'),
                'class' => 'option_website'
            ]
        );
        $this->setForm($form);
        return parent::_prepareForm();
    }

    /**
     * Prepare label for tab
     *
     * @return string
     */
    public function getTabLabel()
    {
        return __('Import Product Allocation');
    }

    /**
     * Prepare title for tab
     *
     * @return string
     */
    public function getTabTitle()
    {
        return $this->getTabLabel();
    }

    /**
     * Can show tab in tabs
     *
     * @return boolean
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * Tab is hidden
     *
     * @return boolean
     */
    public function isHidden()
    {
        return false;
    }
}
