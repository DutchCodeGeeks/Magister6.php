# Magister6.php
Another simple PHP API library for Magister 6

# Requirements
PHP 5.4+

# Usage
```php
require 'lib/Magister6.php';

$magister = new Magister6('school', 'username', 'password');

$magister->userData->getAdditionalInfo();
var_dump($magister->profile);
```
