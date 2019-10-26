<?php

namespace Xigen\CliCreateCustomer\Helper;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Customer\Model\AccountConfirmation;
use Magento\Customer\Model\EmailNotificationInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\MailException;
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
    const KEY_SENDEMAIL = 'send-email';

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
     * @var EmailNotificationInterface
     */
    private $emailNotification;

    /**
     * @var AccountConfirmation
     */
    private $accountConfirmation;

    /**
     * @var EmailNotificationInterface
     */
    private $emailNotificationInterface;

    /**
     * Customer constructor.
     * @param Context $context
     * @param CustomerInterfaceFactory $customerInterfaceFactory
     * @param CustomerRepositoryInterface $customerRepositoryInterface
     * @param EncryptorInterface $encryptorInterface
     * @param LoggerInterface $logger
     * @param AccountConfirmation $accountConfirmation
     */
    public function __construct(
        Context $context,
        CustomerInterfaceFactory $customerInterfaceFactory,
        CustomerRepositoryInterface $customerRepositoryInterface,
        EncryptorInterface $encryptorInterface,
        LoggerInterface $logger,
        AccountConfirmation $accountConfirmation,
        EmailNotificationInterface $emailNotificationInterface
    ) {
        $this->customerInterfaceFactory = $customerInterfaceFactory;
        $this->customerRepositoryInterface = $customerRepositoryInterface;
        $this->encryptorInterface = $encryptorInterface;
        $this->logger = $logger;
        $this->accountConfirmation = $accountConfirmation;
        $this->emailNotificationInterface = $emailNotificationInterface;
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
            if ($this->data->getOption(self::KEY_SENDEMAIL)) {
                $this->sendEmailConfirmation($customer, null, $hashedPassword);
            }
            $this->exception = false;
            return $customer;
        } catch (\Exception $e) {
            $this->logger->critical($e);
            $this->exception = $e;
            return false;
        }
    }

    /**
     * Send Email Confirmation
     * @param CustomerInterface $customer
     * @param string $redirectUrl
     * @param string $hash
     */
    protected function sendEmailConfirmation(CustomerInterface $customer, $redirectUrl = '', $hash = '')
    {
        try {
            $templateType = EmailNotificationInterface::NEW_ACCOUNT_EMAIL_REGISTERED;
            if ($this->isConfirmationRequired($customer) && $hash != '') {
                $templateType = EmailNotificationInterface::NEW_ACCOUNT_EMAIL_CONFIRMATION;
            } elseif ($hash == '') {
                $templateType = EmailNotificationInterface::NEW_ACCOUNT_EMAIL_REGISTERED_NO_PASSWORD;
            }
            $this->emailNotificationInterface->newAccount(
                $customer,
                $templateType,
                $redirectUrl,
                $customer->getStoreId()
            );
        } catch (MailException $e) {
            // If we are not able to send a new account email, this should be ignored
            $this->logger->critical($e);
        } catch (\UnexpectedValueException $e) {
            $this->logger->error($e);
        }
    }

    /**
     * Is confirmation required
     * @param $customer
     * @return bool
     */
    protected function isConfirmationRequired($customer)
    {
        return $this->accountConfirmation->isConfirmationRequired(
            $customer->getWebsiteId(),
            $customer->getId(),
            $customer->getEmail()
        );
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
