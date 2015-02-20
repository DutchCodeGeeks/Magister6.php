<?php
include("lib/Magister6.php");

$magister = new Magister6('school', 'username', 'password');

$magister->userData->getAdditionalInfo();
var_dump($magister->profile);

//$studyguide = $magister->studyguide->get(1948);
//var_dump($studyguide);



?>