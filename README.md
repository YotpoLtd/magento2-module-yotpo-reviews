 Magento 2 Yotpo Extension
==========================

This library includes the files of the Yotpo Reviews extension.
The directories hierarchy is as positioned in a standard magento 2 project library

This library will also include different version packages as magento 2 extensions



## Requirements

magento 2.0 +

## Installation

The Magento 2 module can be installed with Composer (https://getcomposer.org/download/).
From the command line, do the following in your Magento 2 installation directory:

```composer require yotpo/module-review```

```sudo bin/magento setup:upgrade```

###Usage

After the installation, Go to The Magento 2 admin panel

Go to Stores -> Settings -> Configuration, change store view (not to be default config) and click on Yotpo Product Reviews Software on the left sidebar

Insert Your account app key and secret

### Advanced

To insert the widget manually on your product page add the following code in one of your product .phtml files 

```<?php $this->helper('Yotpo\Yotpo\Helper\Data')->showWidget($this); ?>``` 

To insert the bottomline manually on your catalog page add the following code in Magento\Catalog\view\frontend\templates\product\list.phtml

``` <?php $this->helper('Yotpo\Yotpo\Helper\Data')->showBottomline($this, $_product); ?>``` 
