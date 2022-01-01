<?php

namespace Commercers\ProductAllocation\Controller\Manage;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\LocalizedException;

class AddToCart extends \Magento\Framework\App\Action\Action
{
    protected $resultPageFactory;
    protected $_allocationModelFactory;
    protected $_dataHelperFactory;
    protected $productRepository;
    private $checkoutSessionFactory;
    private $cartRepository;
    protected $cartModelFactory;
    protected $_layout;
    public function __construct(
        \Magento\Framework\View\LayoutInterface $layout,
        \Magento\Checkout\Model\CartFactory $cartModelFactory,
        \Magento\Quote\Api\CartRepositoryInterface $cartRepository,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Commercers\ProductAllocation\Helper\DataFactory $dataHelperFactory,
        \Commercers\ProductAllocation\Model\AllocationFactory $allocationModelFactory,
        Context $context
    ) {
        parent::__construct($context);
        $this->_layout = $layout;
        $this->cartModelFactory = $cartModelFactory;
        $this->cartRepository = $cartRepository;
        $this->_checkoutSession = $checkoutSession;
        $this->productRepository = $productRepository;
        $this->_dataHelperFactory = $dataHelperFactory;
        $this->_allocationModelFactory =$allocationModelFactory;
    }
    public function execute()
    {
        try {
            $result['is_error'] = false;
            $result['message'] = true;
            $params = $this->getRequest()->getParams();
            $enterQty = $params["qty"];
            $allocationModel = $this->_allocationModelFactory->create()->load($params["allocation_id"]);
            $cartSession = $this->_checkoutSession->getQuote();
            $result = $cartSession->getAllItems();
            foreach ($result as $cartItem) {
                if ($cartItem->getProduct()->getSku() == $allocationModel->getSku()) {
                    $enterQty = $cartItem->getQty() + $enterQty;
                }
            }
            if($enterQty > $allocationModel->getQty()){
                $result['message'] = false;
                $dataHelper = $this->_dataHelperFactory->create();
                $result['notice'] = __($dataHelper->getMessageError());
            }else{
                if (isset($params['qty'])) {
                    $filter = new \Zend_Filter_LocalizedToNormalized(
                        ['locale' => $this->_objectManager->get(
                            \Magento\Framework\Locale\ResolverInterface::class
                        )->getLocale()]
                    );
                    $params['qty'] = $filter->filter(strval($params['qty']));
                }
                $product = $this->productRepository->get($allocationModel->getSku());
                $nameProduct = $product->getName();
                $cart = $this->cartModelFactory->create();
                $cart->addProduct($product,$params);
                $cart->save();
                $this->_eventManager->dispatch(
                    'checkout_cart_add_product_complete',
                    ['product' => $product, 'request' => $this->getRequest(), 'response' => $this->getResponse()]
                );
                if (!$cart->getQuote()->getHasError()){
                    $result['message'] = true;
                    $result['notice'] = $nameProduct. ' ' .__('was added to your shopping cart.');
//                $result['notice'] = __('%1 was added to your shopping cart.',$nameProduct);
                }else{
                    $result = array(
                        'message' => false,
                        'notice' => __('Cannot add the item to shopping cart.')
                    );
                }
            }
        } catch (LocalizedException $e) {
            $result = [
                'is_error' => true,
                'notice' => $e->getMessage()
            ];
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
            $result = [
                'is_error' => true,
                'notice' => __('We are unable to process your request. Please, try again later.')
            ];
        }
        return $this->resultFactory
            ->create(\Magento\Framework\Controller\ResultFactory::TYPE_JSON)
            ->setData($result);
    }

    protected function _initProduct()
    {
        $productId = (int)$this->getRequest()->getParam('product');
        if ($productId) {
            $storeId = $this->_objectManager->get(
                \Magento\Store\Model\StoreManagerInterface::class
            )->getStore()->getId();
            try {
                return $this->productRepository->getById($productId, false, $storeId);
            } catch (NoSuchEntityException $e) {
                return false;
            }
        }
        return false;
    }
}