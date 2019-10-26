# Magento 2 Cli Create Customer

Create customer via CLI with supplied parameters

# Install instructions

`composer require dominicwatts/clicreatecustomer`

`php bin/magento setup:upgrade`

# Usage instruction

    xigen:clicreatecustomer:create
        [-f|--customer-firstname CUSTOMER-FIRSTNAME]
        [-l|--customer-lastname CUSTOMER-LASTNAME]
        [-e|--customer-email CUSTOMER-EMAIL]
        [-p|--customer-password CUSTOMER-PASSWORD]
        [-w|--website WEBSITE]
        [-s|--send-email [SEND-EMAIL]]

    php bin/magento xigen:clicreatecustomer:create -f "Dave" -l "Smith" -e "dave@example.com" -p "test123" -w 1

    php bin/magento xigen:clicreatecustomer:create -f "Dave" -l "Smith" -e "dave@example.com" -p "test123" -w 1 -s 1
