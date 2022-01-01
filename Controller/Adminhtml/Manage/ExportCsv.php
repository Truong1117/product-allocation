<?php
namespace Commercers\ProductAllocation\Controller\Adminhtml\Manage;
class ExportCsv extends \Magento\Backend\App\Action
{
    protected $filesystem;
    protected $_directoryList;
    protected $csvProcessor;
    public function __construct(
        \Commercers\ProductAllocation\Model\ResourceModel\Allocation\CollectionFactory $allocationCollectionFactory,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\File\Csv $csvProcessor,
        \Magento\Framework\Filesystem $filesystem
    )
    {
        $this->_allocationCollectionFactory = $allocationCollectionFactory;
        $this->filesystem = $filesystem;
        $this->_directoryList = $directoryList;
        $this->csvProcessor = $csvProcessor;
        parent::__construct($context);
    }

    public function execute()
    {
        $allocationCollection = $this->_allocationCollectionFactory->create();
//        $productAllocationData = $allocationCollection->getData();
        $headerLine = 'Label';
        $fieldsClean = [];
        //remove ; from fields
        foreach ($allocationCollection->getData() as $allocation){
            array_push($fieldsClean, str_replace(';', '', $allocation));
        }

        //dpd 'glues' with a pipe symbol
        $content = $headerLine . implode(';', $fieldsClean);

        $this->createFile($content);
    }

    protected function createFile($content)
    {
        try{
            $filePath = $this->_directoryList->getPath('var') . DIRECTORY_SEPARATOR . 'productallocation_importdata';
            $fileName = (new \DateTime())->format('YmdHis') . rand(100,999) . '.csv';

            $filename_out_backup = $filePath.DIRECTORY_SEPARATOR.$fileName;

            // local folder
            $om = \Magento\Framework\App\ObjectManager::getInstance();
            $filesystem = $om->get('Magento\Framework\Filesystem');
            $directoryList = $om->get('Magento\Framework\App\Filesystem\DirectoryList');
            $folderExport = $filesystem->getDirectoryWrite($directoryList::VAR_DIR);
            // $media->writeFile("uploads/ordermanagement/local/" . $fileName, $content);
            $folderExport->writeFile($filename_out_backup, $content);
        }catch(\Exception $e){
        }
    }
}
