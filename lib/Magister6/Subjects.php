<?php

class Magister6_UserData {
	public function __construct(Magister6 $master) {
		$this->master = $master;
	}

	/*
		This method will obtain a subject list
	*/

	public function get() {
		$magister = $this->master;

		$url = $magister->baseURL."/api/leerlingen/".$magister->magisterId."/aanmeldingen".$magister->enrollmentId."/vakken";
		$result = $magister->get($url, $magister->cookieId);
		


	}

}

/*
	Class to reformat json output
*/

class Subject_Adapter {

	public $username, $firstName, $lastName, $dateOfBirth, $email, $mobile, $class;
	public function __construct($user, $fName, $lName, $dOB, $mail=null, $mob=null, $c=null) {
		$this->username = $user;
		$this->firstName = $fName;
		$this->lastName = $lName;
		$this->dateOfBirth = $dOB;
		$this->email = $mail; //optional
		$this->mobile = $mob; //optional
		$this->class = $c; //optional
	}

}
?>