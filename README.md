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

#### Copy the extension files to your workspace
* git clone --recursive https://github.com/YotpoLtd/magento2-plugin.git

#### Install the extension on your magento:
* Go to '~/mage2_vagrant' and edit Vagrantfile
* Under the line  - config.vm.synced_folder ".", "/vagrant", :nfs => { :mount_options => ["dmode=777", "fmode=777"] }
* Add the following line - config.vm.synced_folder "/Users/USERNAME/Development/yotpo-workspace/magento2-plugin/Yotpo", "/vagrant/data/magento2/app/code/Yotpo", create: true
* After updating the Vagrantfile file, run ```vagrant reload``` from your workspace
* Make sure Yotpo extension and its content were updated in vagrant@mage2:/vagrant/data/magento2/app/code$
* Disable Magento reviews - Stores\Configuration\Advanced\Advanced  Magento_Review -> Disable
* After copying yotpo run ```vagrant ssh``` and inside the root folder (/vagrant/data/magento2) run ```php bin/magento setup:upgrade ```
- You can ignore the “Please re-run Magento compile command” as Magento 2 compiles the files automatically on the first page load
* For Yotpo to work locally with the API you need to add your machine's ip address to point to api.yotpo.com on /etc/hosts on your vagrant.
-  The hosts file should look something like this: <br />
27.0.0.1       localhost <br />
127.0.1.1 mage2.dev mage2 <br />
<Your i.p address> api.yotpo.com <br />
* After updating the hosts file, run ```vagrant reload``` from your workspace


###Usage

After the installation, Go to The Magento 2 admin panel

Go to Stores -> Settings -> Configuration, change store view (not to be default config) and click on Yotpo Product Reviews Software on the left sidebar

Insert Your account app key and secret

To insert the widget manually on your product page add the following code in one of your product .phtml files 

```<?php $this->helper('Yotpo\Yotpo\Helper\Data')->showWidget($this); ?>``` 

To insert the bottomline manually on your catalog page add the following code in Magento\Catalog\view\frontend\templates\product\list.phtml

``` <?php $this->helper('Yotpo\Yotpo\Helper\Data')->showBottomline($this, $_product); ?>``` 
