# pocker
A naive PHP client for docker engine API  

# usage
```php
<?php

require 'vendor/autoload.php';

$pocker = Pocker::getInstance();

$pocker->setConfig($Ip, $Port, $ApiVersion);

$pocker->getInfo();
```

# install
recommand composer

> composer require "helica/pocker @dev"

