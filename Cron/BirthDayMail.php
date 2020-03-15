<?php


namespace Zunami\BirthDay\Cron;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Zunami\BirthDay\Api\EmailSenderInterface;

class BirthDayMail {

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * @var EmailSenderInterface
     */
    private $emailSender;

    /**
     * BirthdayEmail constructor.
     * @param CustomerRepositoryInterface $customerRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param DateTime $dateTime
     * @param EmailSenderInterface $emailSender
     */
    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        DateTime $dateTime,
        EmailSenderInterface $emailSender
    ) {
        $this->customerRepository = $customerRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->dateTime = $dateTime;
        $this->emailSender = $emailSender;
    }

    /**
     * Execute the cron
     *
     * @return void
     * @throws LocalizedException
     */
    public function execute()
    {
        $searchCriteria = $this->searchCriteriaBuilder->addFilter(
            'dob',
            '%-' . $this->dateTime->gmtDate('m-d'),
            'like'
        )->create();
        $customers = $this->customerRepository->getList($searchCriteria)->getItems();
        foreach ($customers as $customer) {
            $this->emailSender->send($customer);
        }
    }
}
