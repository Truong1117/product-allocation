# Product-allocation
Magento 2 "Product Allocation" extension allows the to admin manage all products in an order, making sure that products can only be moved to cart if sufficient allocation is available. Extension homepage: https://github.com/Truong1117/product-allocation

## CONTACTS
* Email: dongvantruong1117@gmail.com  

## INSTALLATION

### COMPOSER INSTALLATION
* run composer command:
>`$> composer require commercers/magento2-product-allocation`

### MANUAL INSTALLATION
* extract files from an archive

* deploy files into Magento2 folder `app/code/Commercers/ProductAllocation`

### ENABLE EXTENSION
* enable extension (use Magento 2 command line interface \*):
>`$> php bin/magento module:enable Commercers_ProductAllocation`

* to make sure that the enabled module is properly registered, run 'setup:upgrade':
>`$> php bin/magento setup:upgrade`

* [if needed] re-compile code and re-deploy static view files:
>`$> php bin/magento setup:di:compile`
>`$> php bin/magento setup:static-content:deploy`

Enjoy!

Best regards,

Truong Dong

