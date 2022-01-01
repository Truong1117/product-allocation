<?php
namespace Commercers\ProductAllocation\Block\Adminhtml\Inconsistency\Edit\Tab;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Grid\Extended;
use Magento\Backend\Block\Widget\Tab\TabInterface;
use Magento\Backend\Helper\Data;
use Magento\Framework\Registry;
class Contingents extends Extended implements TabInterface
{
    public $coreRegistry;
    protected $_inconsistencyFactory;
    protected $_allocationFactory;
    public function __construct(
        \Commercers\ProductAllocation\Model\AllocationFactory $allocationFactory,
        \Commercers\ProductAllocation\Model\InconsistencyFactory $inconsistencyFactory,
        Context $context,
        Registry $coreRegistry,
        Data $backendHelper,
        array $data = []
    ) {
        $this->_allocationFactory = $allocationFactory;
        $this->_inconsistencyFactory = $inconsistencyFactory;
        $this->coreRegistry = $coreRegistry;
        parent::__construct($context, $backendHelper, $data);
    }
    public function _construct()
    {
        $this->setId('inconsistency_grid');
        $this->setDefaultSort('allocation_id');
        $this->setDefaultDir('DESC');
        $this->setUseAjax(true);
    }
    protected function _prepareCollection()
    {
        $collection = $this->getProductAllocations();
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
//        $this->addColumn('inconsistency_id', array(
//            'type'      => 'checkbox',
////            'values'    => $this->getSelectProductAllocation(),
//            'values'    => '',
//            'align'     => 'center',
//            'width'     => '50',
//            'index'     => 'allocation_id',
//            'header_css_class' => 'col-select',
//            'column_css_class' => 'col-select'
//        ));
        $this->addColumn('allocation_id', array(
            'header'    => __('ID'),
            'sortable'  => true,
            'width'     => '60px',
            'index'     => 'allocation_id',
            'type'             => 'number',
            'header_css_class' => 'col-inconsistency_id',
            'column_css_class' => 'col-inconsistency_id'
        ));
        $this->addColumn('user', [
            'header'           => __('User (Email)'),
            'index'            => 'user',
            'header_css_class' => 'col-inconsistency_email',
            'column_css_class' => 'col-inconsistency_email'
        ]);
        $this->addColumn('user_group', [
            'header'           => __('Customer Group'),
            'index'            => 'user_group',
            'renderer'  => '\Commercers\ProductAllocation\Block\Adminhtml\Inconsistency\Edit\Tab\Grid\Renderer\CustomerGroup',
            'header_css_class' => 'col-inconsistency_customer_group',
            'column_css_class' => 'col-inconsistency_customer_group'
        ]);
        $this->addColumn('sku', [
            'header'           => __('Sku'),
            'index'            => 'sku',
            'header_css_class' => 'col-inconsistency_sku',
            'column_css_class' => 'col-inconsistency_sku'
        ]);
        $this->addColumn('website_id', [
            'header'           => __('Website'),
            'index'            => 'website_id',
            'renderer'  => '\Commercers\ProductAllocation\Block\Adminhtml\Inconsistency\Edit\Tab\Grid\Renderer\Website',
            'header_css_class' => 'col-inconsistency_website',
            'column_css_class' => 'col-inconsistency_website'
        ]);
        $this->addColumn('qty', [
            'header'           => __('Quantity'),
            'index'            => 'qty',
            'type'             => 'number',
            'header_css_class' => 'col-qty',
            'column_css_class' => 'col-qty'
        ]);
        $this->addColumn('new_qty', [
            'header'           => __('New Quantity'),
            'index'            => 'new_qty',
            'renderer' => 'Commercers\ProductAllocation\Block\Adminhtml\Inconsistency\Edit\Tab\Grid\Renderer\NewQuantity',
            'header_css_class' => 'col-new_qty',
            'column_css_class' => 'col-new_qty'
        ]);
        return parent::_prepareColumns();
    }

    protected function _addColumnFilterToCollection($column)
    {
        // Set custom filter for in customer flag
        if ($column->getId() == 'in_productallocation') {
            $productAllocationIds = $this->getSelectProductAllocation();
            if (empty($productAllocationIds)) {
                $productAllocationIds = 0;
            }
            if ($column->getFilter()->getValue()) {
                $this->getCollection()->addFieldToFilter('allocation_id',['in' => $productAllocationIds]);
            }
            else {
                $this->getCollection()->addFieldToFilter('allocation_id', ['nin' => $productAllocationIds]);
            }
        }
        else {
            parent::_addColumnFilterToCollection($column);
        }
        return $this;
    }
//    public function getSelectProductAllocation()
//    {
//        $idx = $this->getRequest()->getParam('idx',0);
//        $inconsistencyId = $this->getRequest()->getParam('inconsistency_id');
//        $arrProductAllocationIds = [];
//        $arrProductAllocationIds = $this->getSelectedDataProductAllocation($inconsistencyId);
////        if(isset($inconsistencyId)){
////            $productAllocationIds = $this->getSelectedDataProductAllocation($inconsistencyId);
////            if($productAllocationIds){
////                foreach ($productAllocationIds as $productAllocationId){
////                    array_push($arrProductAllocationIds,$productAllocationId['allocation_id']);
////                }
////            }
////        }
//        $productAllocation = $this->getRequest()->getPost('commercers_productallocation_grid_value_'.$idx,$arrProductAllocationIds);
//        return $productAllocation;
//    }

    public function getSelectedDataProductAllocation($inconsistencyId)
    {
        $inconsistencyModel = $this->_inconsistencyFactory->create()->load($inconsistencyId);
        $productAllocationSku = $inconsistencyModel->getSku();
        //return false;
        $connection= $this->_resource->getConnection();
        $customerTable = $this->_resource->getTableName('commercers_product_allocation');
        $sql = "SELECT main_table.sku FROM $customerTable AS main_table WHERE sku=$productAllocationSku";
        $result = $connection->query($sql);
        return $result;
    }
    public function getTabTitle()
    {
        return $this->getTabLabel();
    }
    public function isHidden()
    {
        return false;
    }
    public function getTabLabel()
    {
        return __('Inconsistency Edit');
    }
    public function canShowTab()
    {
        return true;
    }
    public function getGridUrl()
    {
        return $this->getUrl('productallocation/inconsistency/grid', ['inconsistency_id' => $this->_request->getParam('inconsistency_id'), '_current' => true]);
    }

    public function getTabUrl()
    {
        return $this->getUrl('productallocation/inconsistency/allocation', ['inconsistency_id' => $this->_request->getParam('inconsistency_id'), '_current' => true]);
    }
    public function getProductAllocations(){
        return $this->coreRegistry->registry('product_allocation_inconsistency');
    }
}
