## TinyBttn

The code for the Magento module.

[Bttn Result](./bttn_result.php)	The main code body ... receives a OneID-provided input, posts data to TinyBttn, and handles result

[Bttn](./bttn.php)	The main interface page ... determines whether or not to request OneID credentials, posts data to `Bttn_Result.php`

[Catalog Sync](./catalog_sync.php)	The code to sync the Magento product catalog with TinyBttn

[Create](./create.php)	The code to handle programatically creating discounts

[Drop Down](./dropdown.php)	The code to determine what dropdown point-of-sale verification options to show

[Result](./result.php)	The code to post point-of-sale verification data to TinyBttn and handle the result

[Support](./support.php)	Common code, mostly the CURL function

[Trasx](./trnsx.php)	The code to correctly get/format data after an order is complete and post it to TinyBttn