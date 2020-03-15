<?php

namespace Zunami\BirthDay\Model;

use Magento\Customer\Api\Data\CustomerInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\App\Emulation;
use Magento\Store\Model\ScopeInterface;
use Zunami\BirthDay\Api\EmailSenderInterface;

class EmailSender implements EmailSenderInterface {
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var TransportBuilder
     */
    private $transportBuilder;

    /**
     * @var StateInterface
     */
    private $inlineTranslation;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Emulation
     */
    private $emulation;

    /**
     * EmailSender constructor.
     * @param LoggerInterface $logger
     * @param TransportBuilder $transportBuilder
     * @param StateInterface $inlineTranslation
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param Emulation $emulation
     */
    public function __construct(
        LoggerInterface $logger,
        TransportBuilder $transportBuilder,
        StateInterface $inlineTranslation,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        Emulation $emulation
    ) {
        $this->logger = $logger;
        $this->transportBuilder = $transportBuilder;
        $this->inlineTranslation = $inlineTranslation;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->emulation = $emulation;
    }

    /**
     * Send birthday email to customer
     * @param CustomerInterface $customer
     * @return bool
     */
    public function send(CustomerInterface $customer): void {
        $enabled = $this->scopeConfig->getValue(
            'email_section/sendmail/enabled',
            ScopeInterface::SCOPE_STORE,
            $customer->getStoreId()
        );
        if (!$enabled) {
            return;
        }

        $this->inlineTranslation->suspend();
        $this->emulation->startEnvironmentEmulation($customer->getStoreId());

        try {
            $senderIdentity = $this->scopeConfig->getValue(
                'email_section/sendmail/sender',
                ScopeInterface::SCOPE_STORE,
                $customer->getStoreId()
            );
            $this->transportBuilder
                ->setTemplateIdentifier(
                    $this->scopeConfig->getValue(
                        'email_section/sendmail/email_template',
                        ScopeInterface::SCOPE_STORE,
                        $customer->getStoreId()
                    )
                )
                ->setTemplateOptions(
                    [
                        'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                        'store' => $customer->getStoreId(),
                    ]
                )
                ->setTemplateVars($vars = [
                    'customer' => $customer,
                    'store' => $this->storeManager->getStore($customer->getStoreId()),
                    'coupons' => $this->coupon1()["code"]
                ])
                ->setFrom(
                    [
                        'name' => $this->scopeConfig->getValue(
                            'trans_email/ident_' . $senderIdentity . '/name',
                            ScopeInterface::SCOPE_STORE,
                            $customer->getStoreId()
                        ),
                        'email' => $this->scopeConfig->getValue(
                            'trans_email/ident_' . $senderIdentity . '/email',
                            ScopeInterface::SCOPE_STORE,
                            $customer->getStoreId()
                        ),
                    ]
                )
                ->addTo($customer->getEmail());

            $transport = $this->transportBuilder->getTransport();
            $transport->sendMessage();

        } catch (\Exception $e) {
            $this->logger->debug('Cannot send  Birthday Email to the customer ID ' . $customer->getId() . '. Error: ' . $e->getMessage());
        }
        $this->inlineTranslation->resume();
        $this->emulation->stopEnvironmentEmulation();
    }

    private $permitted_chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

    function generate_string($input, $strength = 4) {
        $input_length = strlen($input);
        $random_string = '';
        for($i = 0; $i < $strength; $i++) {
            $random_character = $input[mt_rand(0, $input_length - 1)];
            $random_string .= $random_character;
        }

        return $random_string;
    }

    public function coupon1() {

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $state = $objectManager->get('Magento\Framework\App\State');

        $coupon['name'] = 'Geburtstag - ' . $this->generate_string($this->permitted_chars);
        $coupon['desc'] = 'Discount for Birthday coupon. ' . $this->generate_string($this->permitted_chars);
        $coupon['start'] = date('Y-m-d');
        $coupon['end'] = '';
        $coupon['max_redemptions'] = 1;
        $coupon['discount_type'] ='by_percent';
        $coupon['discount_amount'] = 15;
        $coupon['flag_is_free_shipping'] = 'no';
        $coupon['redemptions'] = 1;
        $coupon['code'] ='Geburtstag-'. $this->generate_string($this->permitted_chars); //this code will normally be autogenetated but i am hard coding for testing purposes

        $shoppingCartPriceRule = $objectManager->create('Magento\SalesRule\Model\Rule');
        $shoppingCartPriceRule->setName($coupon['name'])
            ->setDescription($coupon['desc'])
            ->setFromDate($coupon['start'])
            ->setToDate($coupon['end'])
            ->setUsesPerCustomer($coupon['max_redemptions'])
            ->setCustomerGroupIds(array('0','1','2','3',))
            ->setIsActive(1)
            ->setSimpleAction($coupon['discount_type'])
            ->setDiscountAmount($coupon['discount_amount'])
            ->setDiscountQty(1)
            ->setApplyToShipping($coupon['flag_is_free_shipping'])
            ->setTimesUsed($coupon['redemptions'])
            ->setWebsiteIds(array('1'))
            ->setCouponType(2)
            ->setCouponCode($coupon['code'])
            ->setUsesPerCoupon(NULL);
        // $shoppingCartPriceRule->save();

        return array("code"=>$coupon['code'], $shoppingCartPriceRule->save());
    }
}
