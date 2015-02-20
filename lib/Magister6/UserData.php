<?php

/*
	As the only exception, this class will set required user data and returns a class for the profile variable.
*/

class Magister6_UserData {
	public function __construct(Magister6 $master) {
        $this->master = $master;
    }

    /*
		This method will obtain required user data
    */

    public function get($username) {
    	$magister = $this->master;

    	$userDataUrl = $magister->baseURL."/api/account";
		$userData = $magister->get($userDataUrl, $magister->cookieId);
		
		$magister->magisterId = $userData->Persoon->Id;
		$dateOfBirth = $magister->parseDateTime($userData->Persoon->Geboortedatum);
		$lastName = $userData->Persoon->Tussenvoegsel.' '.$userData->Persoon->Achternaam;

		return new Profile_Adapter($username, $userData->Persoon->Roepnaam, $lastName, $dateOfBirth);
		//return array("username" => $username, "firstName" => $userData->Persoon->Roepnaam, "lastName" => $userData->Persoon->Tussenvoegsel.' '.$userData->Persoon->Achternaam, "dateOfBirth" => $dateOfBirth);

    }

    /*
		This method will obtain some additional user info: email address, mobile number and the class
    */
	
	public function getAdditionalInfo() {
		$magister = $this->master;

		//Request to obtain email address and mobile number
		$url = $magister->baseURL."/api/personen/".$magister->magisterId."/profiel";
		$result =  $magister->get($url, $magister->cookieId);

		//Get and set email address and mobile number
		$magister->profile->email = $result->EmailAdres;
		$magister->profile->mobile = $result->Mobiel;

		//Request to obtain class
		$url = $magister->baseURL."/api/personen/".$magister->magisterId."/aanmeldingen?geenToekomstige=false&peildatum=".date("Y-m-d");
		$result =  $magister->get($url, $magister->cookieId);

		//Get and set class
		$class = $result->Items[0]->Groep->Omschrijving;
		$magister->profile->class = preg_replace('/Klas\s+/i', '', $class);

		return true;

	}
}

/*
	Class to reformat json output
*/

class Profile_Adapter {

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