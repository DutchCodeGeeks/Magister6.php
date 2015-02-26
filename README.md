# Magister6.php
Another simple PHP API library for Magister 6 with sessions!

# Requirements
PHP 5.4+ (because of a custom SessionHandler)

# Simple Usage
```php
require 'lib/Magister6.php';

$magister = new Magister6('school', 'username', 'password');

var_dump($magister->profile);
```

The best part is that it can be used across multiple pages **Automatically!**. This works by a custom SessionHandler, so once the user is logged in, you can just use this on other pages:
```php
require 'lib/Magister6.php';
$magister = new Magister6();

if($magister->loggedIn){
# do some cool stuff
var_dump($magister->profile);
}
```

# Methods
* **Studyguides:**
```php
#Get list of studyguides:
$magister->studyguide->getList();

#Get the latest studyguide id of all subjects (this uses a very simple algorithm)
$magister->studyguide->getSimpleList();

#Just get all studyguide content by providing a studyguide id. 
$magister->studyguide->get($id);
```
* **Assignments:**
```php
#Get list of all assignments
$magister->assignment->getList();

#Get all assignments (and submitted assignments) by providing a subject string
$magister->assignment->getList($subjectString);

#Just get all details of a assignment by providing a assignment id.
$magister->assignment->get($id);
```

* **Course Materials:**
```php
#Get *ONE* course material by providing a subject string
$magister->assignment->get($subjectString);
```

# Examples
* **Obtainig a profile pic of the user:**
```php
<?php
require 'lib/Magister6.php';
$magister = new Magister6();

$magister->fetchPictureHeight = '75'; //Pic height in pixels. Default is 75
$magister->fetchPictureWidth = '75'; //Pic width in pixels. Default is 75
$magister->fetchPictureCrop = true; //Crop the pic. Default is true

$magister->fetchPictureFolder = '/var/www/website.nl/img/profiles/'; //Change this to your wishes. Make sure PHP got write permissions.
$magister->fetchPictureSalt  = '$0m3SuPER$ecretS@lt!'; //Use a salt for setting the file name.

# Now the real deal, this will download it to the folder we just set up:
$fileName = $magister->userData->getPicture();
# This will return the *file name* only (NOT the folder)
echo '<img src="img/profiles/'.$fileName.'">';
?>
```