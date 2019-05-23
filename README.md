# pocker
A naive PHP client for docker engine API  
in developing~

# usage
```php
<?php

require 'vendor/autoload.php';

$pocker = Pocker::getInstance();

$pocker->getInfo();
```

# install
recommand composer

> composer require "helica/pocker @dev"

