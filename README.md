# Magento 2 [Yotpo](https://www.yotpo.com/) Extension

---

This library includes the files of the Yotpo Reviews extension.
The directories hierarchy is as positioned in a standard magento 2 project library

This library will also include different version packages as magento 2 extensions

---

## Requirements
Magento 2.0+ (Up to module verion 2.4.5)

Magento 2.1+ (Module version 2.7.5 up to 2.7.7)

Magento 2.2+ (Module version 2.8.0 and above)

## ✓ Install via [composer](https://getcomposer.org/download/) (recommended)
Run the following command under your Magento 2 root dir:

```
composer require yotpo/module-review
php bin/magento maintenance:enable
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy
php bin/magento maintenance:disable
php bin/magento cache:clean
```

## Install manually under app/code
1. Download & place the contents of [Yotpo's Core Module](https://github.com/YotpoLtd/magento2-module-yotpo-core) under {YOUR-MAGENTO2-ROOT-DIR}/app/code/Yotpo/Core.
2. Download & place the contents of this repository under {YOUR-MAGENTO2-ROOT-DIR}/app/code/Yotpo/Yotpo  
3. Run the following commands under your Magento 2 root dir:
```
php bin/magento maintenance:enable
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy
php bin/magento maintenance:disable
php bin/magento cache:clean
```

## Usage

After the installation, Go to The Magento 2 admin panel

Go to Stores -> Settings -> Configuration, change store view (not to be default config) and click on Yotpo Product Reviews Software on the left sidebar

Insert Your account app key and secret

## Advanced

To insert the widget manually on your product page add the following code in one of your product .phtml files

```
<?= $this->helper('Yotpo\Yotpo\Helper\Data')->showWidget($block) ?>
```

To insert the bottomline manually on your catalog page add the following code in Magento\Catalog\view\frontend\templates\product\list.phtml

```
<?= $this->helper('Yotpo\Yotpo\Helper\Data')->showBottomline($block, $_product) ?>
```

---

https://www.yotpo.com/

Copyright © 2018 Yotpo. All rights reserved.  

![Yotpo Logo](https://yap.yotpo.com/assets/images/logo_login.png)
