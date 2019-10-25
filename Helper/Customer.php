<?php

namespace Xigen\CliCreateCustomer\Helper;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\Input;

/**
 * Customer Helper class
 */
class Customer extends AbstractHelper
{
    const KEY_EMAIL = 'customer-email';
    const KEY_FIRSTNAME = 'customer-firstname';
    const KEY_LASTNAME = 'customer-lastname';
    const KEY_PASSWORD = 'customer-password';
    const KEY_WEBSITE = 'website';

    /**
     * @var CustomerInterfaceFactory
     */
    private $customerInterfaceFactory;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepositoryInterface;

    /**
     * @var EncryptorInterface
     */
    private $encryptorInterface;

    /**
     * @var Symfony\Component\Console\Input\InputInterface
     */
    private $data;

    /**
     * @var Exception
     */
    private $exception;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Customer constructor.
     * @param Context $context
     * @param CustomerInterfaceFactory $customerInterfaceFactory
     * @param CustomerRepositoryInterface $customerRepositoryInterface
     * @param EncryptorInterface $encryptorInterface
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        CustomerInterfaceFactory $customerInterfaceFactory,
        CustomerRepositoryInterface $customerRepositoryInterface,
        EncryptorInterface $encryptorInterface,
        LoggerInterface $logger
    ) {
        $this->customerInterfaceFactory = $customerInterfaceFactory;
        $this->customerRepositoryInterface = $customerRepositoryInterface;
        $this->encryptorInterface = $encryptorInterface;
        $this->logger = $logger;
        parent::__construct($context);
    }

    /**
     * @param Input $input
     */
    public function setData(Input $input)
    {
        $this->data = $input;
    }

    /**
     * Create Customer record
     * @return bool|\Magento\Customer\Api\Data\CustomerInterface
     */
    public function createCustomer()
    {
        $websiteId = $this->data->getOption(self::KEY_WEBSITE);
        $email = $this->data->getOption(self::KEY_EMAIL);
        $firstname = $this->data->getOption(self::KEY_FIRSTNAME);
        $lastname = $this->data->getOption(self::KEY_LASTNAME);
        
        try {
            if (!$websiteId || !$email || !$firstname || !$lastname) {
                throw new LocalizedException(__("One or more parameters missing"));
            }

            if (!\Zend_Validate::is(trim($firstname), 'NotEmpty')) {
                throw new LocalizedException(__("Invalid first name"));
            }
            
            if (!\Zend_Validate::is(trim($lastname), 'NotEmpty')) {
                throw new LocalizedException(__("Invalid last name"));
            }

            if (!\Zend_Validate::is(trim($email), 'EmailAddress')) {
                throw new LocalizedException(__("Invalid email address"));
            }

            if (!is_numeric($websiteId)) {
                throw new LocalizedException(__("Invalid website ID"));
            }

            $customer = $this->customerInterfaceFactory
                ->create()
                ->setWebsiteId((int) $websiteId)
                ->setEmail($email)
                ->setFirstname($firstname)
                ->setLastname($lastname);
        
            $hashedPassword = $this->encryptorInterface->getHash($this->data->getOption(self::KEY_PASSWORD), true);
            $customer = $this->customerRepositoryInterface->save($customer, $hashedPassword);
            $this->exception = false;
            return $customer;
        } catch (\Exception $e) {
            $this->logger->critical($e);
            $this->exception = $e;
            return false;
        }
    }

    /**
     * Get exception
     * @return Exception
     */
    public function getException()
    {
        return $this->exception;
    }
}
