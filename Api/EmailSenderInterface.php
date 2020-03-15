<?php


namespace Zunami\BirthDay\Api;


use Magento\Customer\Api\Data\CustomerInterface;

interface EmailSenderInterface {
    /**
     * Send birthday email to customer
     * @param CustomerInterface $customer
     * @return bool
     */
    public function send(CustomerInterface $customer): void;

}
