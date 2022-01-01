<?php

namespace Commercers\ProductAllocation\Controller\Adminhtml\ImportData;

use Magento\Framework\App\Filesystem\DirectoryList;
use Commercers\ProductAllocation\Model\EavInstall;

class Save extends \Magento\Backend\App\Action
{
    protected $_fileUploaderFactory;
    protected $_filesystem;
    protected $_fileCsv;
    protected $_storeManager;
    protected $registry;
    protected $_fileio;
    protected $eavInstall;
    public function __construct(
        \Magento\Framework\Registry $registry,
        \Magento\MediaStorage\Model\File\UploaderFactory $fileUploaderFactory,
        \Magento\Framework\Filesystem $fileSystem,
        \Magento\Framework\File\Csv $fileCsv,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
        \Magento\Framework\Filesystem\Io\File $fileio,
        \Magento\Backend\App\Action\Context $context,
        \Commercers\ProductAllocation\Model\EavInstall $eavInstall
    ) {
        $this->_fileUploaderFactory = $fileUploaderFactory;
        $this->_filesystem = $fileSystem;
        $this->_fileCsv = $fileCsv;
        $this->_storeManager = $storeManagerInterface;
        $this->registry = $registry;
        $this->_fileio = $fileio;
        $this->eavInstall = $eavInstall;

        parent::__construct($context);
    }

    public function execute()
    {
        $paramOptionImport = $this->_request->getParam('option_import');
        $paramOptionWebsiteId = $this->_request->getParam('option_website');
        $this->registry->register('isSecureArea', true);
        try {
            $filepath = $this->_uploadFileAndGetName();
            if ($filepath !='' && file_exists($filepath)) {
                chmod($filepath, 0777);
                $data = $this->_fileCsv->getData($filepath);
                if (isset($data[0]) && !empty($data[0])) {
                    try {
                        if($paramOptionImport){
                            $response = $this->eavInstall->replaceProductAllocation($filepath,$paramOptionWebsiteId);
                        }else{
                            $response = $this->eavInstall->addProductAllocation($filepath,$paramOptionWebsiteId);
                        }
                        //Here need show detail message
                        if($response["success"]){
                            $resultMessage = json_encode($response["success_message"]);
                            $this->messageManager->addSuccess($resultMessage);
                        }else{
                            $this->messageManager->addError(__('All data imported was errors. Please look at history for details.'));
                        }
                    } catch (\Exception $e) {
                        $this->messageManager->addError($e->getMessage());
                    }
                    return $this->redirect();
                } else {
                    $this->messageManager->addError('Data Not Found.');
                    return $this->redirect();
                }
            } else {
                $this->messageManager->addError('File not Found.');
                return $this->redirect();
            }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addError($e->getMessage());
        } catch (\RuntimeException $e) {
            $this->messageManager->addError($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addError($e->getMessage());
        }

        return $this->redirect();
    }

    protected function _uploadFileAndGetName()
    {
        $uploader = $this->_fileUploaderFactory->create(['fileId' => 'file']);
        $uploader->setAllowedExtensions(['CSV', 'csv']);
        $uploader->setAllowRenameFiles(true);
        $uploader->setFilesDispersion(false);
        $path = $this->_filesystem->getDirectoryRead(DirectoryList::VAR_DIR)
            ->getAbsolutePath('productallocation_importdata');

        if (!is_dir($path)) {
            $this->_fileio->mkdir($path, '0777', true);
            $this->_fileio->chmod($path, '0777', true);
        }
        $result = $uploader->save($path.'/');
        if (isset($result['file']) && !empty($result['file'])) {
            return $result['path'].$result['file'];
        }
        return false;
    }

    protected function _getKeyValue($row, $headerArray)
    {
        $temp = [];
        foreach ($headerArray as $key => $value) {
            $temp[$value] = $row[$key];
        }
        return $temp;
    }

    protected function redirect(){
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('productallocation/importdata/index');

        return $resultRedirect;
    }
}
