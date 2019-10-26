<?php

namespace Xigen\CliCreateCustomer\Console\Command;

use Magento\Framework\App\State;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Xigen\CliCreateCustomer\Helper\Customer;

/**
 * Create Command class
 */
class Create extends Command
{
    /**
     * @var Customer
     */
    private $customerHelper;

    /**
     * @var State
     */
    private $state;

    /**
     * Create constructor.
     * @param Customer $customerHelper
     * @param State $state
     */
    public function __construct(
        Customer $customerHelper,
        State $state
    ) {
        $this->customerHelper = $customerHelper;
        $this->state = $state;
        parent::__construct();
    }

    /**
     * xigen:clicreatecustomer:create
     *   [-f|--customer-firstname CUSTOMER-FIRSTNAME]
     *   [-l|--customer-lastname CUSTOMER-LASTNAME]
     *   [-e|--customer-email CUSTOMER-EMAIL]
     *   [-p|--customer-password CUSTOMER-PASSWORD]
     *   [-w|--website WEBSITE]
     *   [-s|--send-email [SEND-EMAIL]]
     *
     * php bin/magento xigen:clicreatecustomer:create -f "Dave" -l "Smith" -e "dave@example.com" -p "test123" -w 1
     * php bin/magento xigen:clicreatecustomer:create -f "Dave" -l "Smith" -e "dave@example.com" -p "test123" -w 1 -s 1
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return int|void|null
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ) {
        $this->state->setAreaCode(\Magento\Framework\App\Area::AREA_GLOBAL);
        $output->writeln('<info>Creating new user</info>');
        $this->customerHelper->setData($input);

        $customer = $this->customerHelper->createCustomer();

        if ($customer && $customer->getId()) {
            $output->writeln("<info>Created new user</info>");
            $output->writeln((string) __("User ID: %1", $customer->getId()));
            $output->writeln((string) __("First name: %1", $customer->getFirstname()));
            $output->writeln((string) __("Last name: %1", $customer->getLastname()));
            $output->writeln((string) __("Email: %1", $customer->getEmail()));
            $output->writeln((string) __("Website Id: %1", $customer->getWebsiteId()));
            if ($input->getOption(Customer::KEY_SENDEMAIL)) {
                $output->writeln("Sending Email");
            }
        } else {
            $output->writeln("<error>Problem creating new user</error>");
            if ($e = $this->customerHelper->getException()) {
                $output->writeln((string) __("<error>%1</error>", $e->getMessage()));
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName("xigen:clicreatecustomer:create");
        $this->setDescription("Create customer with supplied arguments");
        $this->setDefinition([
            new InputOption(Customer::KEY_FIRSTNAME, '-f', InputOption::VALUE_REQUIRED, '(Required) First name'),
            new InputOption(Customer::KEY_LASTNAME, '-l', InputOption::VALUE_REQUIRED, '(Required) Last name'),
            new InputOption(Customer::KEY_EMAIL, '-e', InputOption::VALUE_REQUIRED, '(Required) Email'),
            new InputOption(Customer::KEY_PASSWORD, '-p', InputOption::VALUE_REQUIRED, '(Required) Password'),
            new InputOption(Customer::KEY_WEBSITE, '-w', InputOption::VALUE_REQUIRED, '(Required) Website ID'),
            new InputOption(Customer::KEY_SENDEMAIL, '-s', InputOption::VALUE_OPTIONAL, '(1/0) Send email (default 0)')
        ]);
        parent::configure();
    }
}
