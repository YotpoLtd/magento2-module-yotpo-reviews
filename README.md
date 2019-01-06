 Magento 2 Yotpo Extension
==========================

This library includes the files of the Yotpo Reviews extension.
The directories hierarchy is as positioned in a standard magento 2 project library

This library will also include different version packages as magento 2 extensions


## Requirements

magento 2.0 +

## ✓ Install via [composer](https://getcomposer.org/download/) (recommended)
Run the following command under your Magento 2 root dir:

```
composer require yotpo/module-review
php bin/magento maintenance:enable
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy
php bin/magento maintenance:disable
php bin/magento cache:flush
```

## Install manually under app/code
Download & place the contents of this repository under {YOUR-MAGENTO2-ROOT-DIR}/app/code/Yotpo/Yotpo  
Then, run the following commands under your Magento 2 root dir:
```
php bin/magento maintenance:enable
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy
php bin/magento maintenance:disable
php bin/magento cache:flush
```

## Usage

After the installation, Go to The Magento 2 admin panel

Go to Stores -> Settings -> Configuration, change store view (not to be default config) and click on Yotpo Product Reviews Software on the left sidebar

Insert Your account app key and secret

## Advanced

To insert the widget manually on your product page add the following code in one of your product .phtml files

```
<?php $this->helper('Yotpo\Yotpo\Helper\Data')->showWidget($this); ?>
```

To insert the bottomline manually on your catalog page add the following code in Magento\Catalog\view\frontend\templates\product\list.phtml

```
<?php $this->helper('Yotpo\Yotpo\Helper\Data')->showBottomline($this, $_product); ?>
```

---

https://www.yotpo.com/

Copyright © 2018 Yotpo. All rights reserved.  

![Yotpo Logo](https://yap.yotpo.com/assets/images/logo_login.png)
