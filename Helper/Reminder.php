<?php
namespace Commercers\ProductAllocation\Helper;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
class Reminder extends AbstractHelper
{
    const XML_PATH_EMAIL_TEMPLATE = 'productallocation/reminder/email_template';
    const XML_PATH_EMAIL_IDENTITY  = 'sales_email/order/identity';
    const XML_PATH_REMINDER_ENABLED = 'productallocation/reminder/enabled';
    protected $_allocationFactory;
    protected $storeManager;
    protected $customerFactory;
    public function __construct(
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Commercers\ProductAllocation\Model\AllocationFactory $allocationFactory,
        Context $context
    ) {
        $this->customerFactory = $customerFactory;
        $this->storeManager = $storeManager;
        $this->_allocationFactory = $allocationFactory;
        parent::__construct($context);
    }
    public function sendReminders(){
        $allocationCollection = $this->_allocationFactory->create()->getCollection();
        $commercersProductAllocationReminderTable = "commercers_product_allocation";
        $allocationCollection->getSelect()
            ->join(
                array('reminder' => $commercersProductAllocationReminderTable),
                'reminder.product_sku= main_table.sku AND reminder.store_id = main_table.store_id',
                array()
        );
        $allocationCollection->addFieldToFilter('quantity', array('gt' => 0));
        $allocationCollection->addFieldToFilter('reminder.date', array('eq' => date('Y-m-d')));
        $data = array();
        $stores = array();
        foreach ($allocationCollection as $item){
            $storeId = $item->getStoreId();
            if (!isset($data[$storeId])){
                $data[$storeId] = array();
            }
            if (!isset($data[$storeId][$item->getEmail()])){
                $data[$storeId][$item->getEmail()] = array();
            }
            $index = count($data[$storeId][$item->getEmail()]);
            if (!isset($data[$storeId][$item->getEmail()]['items'])){
                $data[$storeId][$item->getEmail()]['items'] = array();
            }
            $data[$storeId][$item->getEmail()]['items'][] = $item;
            if (!isset($stores[$storeId])){
                $stores[$storeId] = $this->storeManager->getStore($storeId);
            }
            $store = $stores[$storeId];
            $customer = $this->customerFactory->create()->setWebsiteId($store->getWebsiteId())
                ->loadByEmail($item->getEmail());
            if ($customer && $customer->getId() && !isset($data[$storeId][$item->getEmail()]['customer'])){
                $data[$storeId][$item->getEmail()]['customer'] = $customer;
            }
        }
        foreach ($data as $storeId => $storeData){
            foreach ($storeData as $email => $reminder){
                if (!empty($reminder['customer'])){
                    $this->sendReminderEmail(array(
                        'email' => $email,
                        'store_id' => $storeId,
                        'items' => $reminder['items'],
                        'customer' => $reminder['customer']
                    ));
                }
            }
        }
        return $allocationCollection;
    }

    public function sendEmail($template,$emailTemplateVariables)
    {
        $email = $emailTemplateVariables['email'];
        $customer = $emailTemplateVariables['customer'];
        $items = $emailTemplateVariables['items'];
        $reminder = [];
        $reminder["customer_name"] =$customer->getFirstname() . " " . $customer->getLastname();
        $this->_template = $this->getTemplateId($template);
        $this->_inlineTranslation->suspend();
        $senderInfo = [
            'email' => $this->getConfigValue('trans_email/ident_general/email',$this->_storeManager->getStore()->getId()),
            'name' => $this->getConfigValue('trans_email/ident_general/name',$this->_storeManager->getStore()->getId())
        ];
        $receiverInfo = [
            'email' => $email,
            'name' => $customer->getFirstname() . " " . $customer->getLastname()
        ];

        $this->generateTemplate($emailTemplateVariables,$senderInfo,$receiverInfo,$items,$reminder);
        try {
            $transport = $this->_transportBuilder->getTransport();
            $transport->sendMessage();
        } catch (\Exception $e) {
            echo $e->getMessage();exit;
        }
        $this->_inlineTranslation->resume();
    }
    protected function generateTemplate($emailTemplateVariables,$senderInfo,$receiverInfo,$items,$reminder)
    {
        $template = $this->_transportBuilder->setTemplateIdentifier($this->_template)
            ->setTemplateOptions(
                [
                    'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                    'store' => $this->_storeManager->getStore()->getId()
                ]
            )
            ->setTemplateVars([
                'items'        => $items,
                'reminder'     => $reminder
            ])
            ->setFrom($senderInfo)
            ->addTo($receiverInfo['email'],$receiverInfo['name'])
            ->setReplyTo($senderInfo['email'],$senderInfo['name']);
        //Set last send reminder at
        return $template;
    }

    protected function getConfigValue($path,$storeId)
    {
        return $this->scopeConfig->getValue(
            $path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    protected function getTemplateId($xmlPath)
    {
        return $this->getConfigValue($xmlPath,$this->_storeManager->getStore()->getId());
    }

    public function sendReminderEmail($emailTempVariables)
    {
        $this->sendEmail(self::XML_PATH_EMAIL_TEMPLATE, $emailTempVariables);
    }

//    protected function sendReminderEmail($data){
//        $email = $data['email'];
//        $storeId = $data['store_id'];
//        $store = $this->storeManager->getStore($storeId);
//        $customer = $data['customer'];
//        $items = $data['items'];
//        $reminder = [];
//        $reminder['customer_name'] = $customer->getFirstname() . " " . $customer->getLastname();
//
//            $reminder->setCustomerName($customer->getFirstname() . " " . $customer->getLastname());
//        $templateId = Mage::getStoreConfig(self::XML_PATH_EMAIL_TEMPLATE, $storeId);
//        $mailer = Mage::getModel('core/email_template_mailer');
//        $mailer->setStoreId($storeId);
//    }
}

