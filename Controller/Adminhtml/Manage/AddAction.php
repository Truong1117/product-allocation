<?php
namespace Commercers\ProductAllocation\Controller\Adminhtml\Manage;
use Commercers\ProductAllocation\Model\Allocation as Allocation;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultFactory;
/**
 * Create CMS page action.
 */
class AddAction extends \Magento\Backend\App\Action implements HttpGetActionInterface
{
    /**
     * Edit A Contact Page
     *
     * @return \Magento\Backend\Model\View\Result\Page|\Magento\Backend\Model\View\Result\Redirect
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function execute()
    {
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        return $resultPage;
    }
}
