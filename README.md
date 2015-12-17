 Magento 2 Yotpo Extension
==========================

This library includes the files of the Yotpo Reviews extension.
The directories hierarchy is as positioned in a standard magento 2 project library

This library will also include different version packages as magento 2 extensions



## Requirements

magento 2.0 +

## Installation
### Magento 2 (store) on Vagrant installation
Follow the installation steps at https://github.com/rgranadino/mage2_vagrant
 On stage 6. recommended to use ```reinstall -s```
  
#### To install the extension on your magento:
* Copy the extension - copy /Yotpo from git to app/code
* Edit app/etc/config.php and add "Yotpo_Yotpo" => 1
* Disable Magento reviews - Stores\Configuration\Advanced\Advanced  Magento_Review -> Disable
* After copying yotpo open vagrant ssh and inside the root folder (/vagrant/data/magento2) run ```php bin/magento setup:upgrade ```
* For Yotpo to wrok locally with the API you need to add your machine's ip address to point to api.yotpo.com on /etc/hosts on your vagrant 

###Usage

After the installation, Go to The Magento 2 admin panel

Go to Stores -> Settings -> Configuration, change store view (not to be default config) and click on Yotpo Product Reviews Software on the left sidebar

Insert Your account app key and secret

To insert the widget manually on your product page add the following code in one of your product .phtml files 

```<?php $this->helper('Yotpo\Yotpo\Helper\Data')->showWidget($this); ?>``` 

To insert the bottomline manually on your catalog page add the following code in Magento\Catalog\view\frontend\templates\product\list.phtml

``` <?php $this->helper('Yotpo\Yotpo\Helper\Data')->showBottomline($this, $_product); ?>``` 
