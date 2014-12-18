# PayU account plugin for osCommerce
-------
Dear User, 
On December 19, 2014 we stop developing and supporting osCommerce plugin. Nevertheless, feel free to create pull requests we will gladly accept.


PayU account is a web application designed as an e-wallet for shoppers willing to open an account, define their payment options, see their purchase history and manage personal profiles.

##Features
The PayU payments osCommerce plugin adds the PayU payment option and enables you to process the following operations in your e-shop:

* Creating a payment order (with discounts included)
* Cancelling a payment order
* Conducting a refund operation (for a whole or partial order)

## Dependencies

The following PHP extensions are required:

* cURL
* hash
* XMLWriter
* XMLReader

## Installation

1. Copy folders (ext, includes) to the osCommerce root folder
2. Open osCommerce administration page
3. Go to the Modules/Payment and click Install Module
4. Click install PayU account
5. Go to PayU account edit
6. Enable module
5. Fill in all required configuration fields:
* POS ID
* POS Auth Key
* Key (MD5)
* Second key (MD5)
7. Save
