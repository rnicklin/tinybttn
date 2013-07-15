## TinyBttn

#### The code for the Magento module.

 - [Bttn](./bttn.php) Front-end code: determines whether or not to request OneID credentials, posts data to bttn_result.php

 - [Bttn Result](./bttn_result.php)	Back-end code: receives credentials, posts data to TinyBttn, and handles the results



##### Other code (not as important right now)



 - [Catalog Sync](./catalog_sync.php)	The code to sync the Magento product catalog with TinyBttn

 - [Create](./create.php)	The code to handle programatically creating discounts

 - [Drop Down](./dropdown.php)	The code to determine what dropdown point-of-sale verification options to show

 - [Result](./result.php)	The code to post point-of-sale verification data to TinyBttn and handle the result

 - [Trnsx](./trnsx.php)	The code to correctly get/format data after an order is complete and post it to TinyBttn