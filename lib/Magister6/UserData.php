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

	public function get($username, $fetchPicture) {
		$magister = $this->master;

		$userDataUrl = $magister->baseURL."/api/account";
		$userData = $magister->get($userDataUrl, $magister->cookieId);
		
		$magister->magisterId = $userData->Persoon->Id;
		$dateOfBirth = $magister->parseDateTime($userData->Persoon->Geboortedatum);
		$lastName = $userData->Persoon->Tussenvoegsel.' '.$userData->Persoon->Achternaam;

		if($fetchPicture){
			//Save profile image
			$fileName = $magister->school.'-'.md5($magister->magisterId.$magister->fetchPictureSalt); //MD5 is fine, it's not a password...
			$this->getPicture($magister->baseURL.'/api/personen/'.$magister->magisterId.'/foto?width=75&height=75&crop=true', $magister->cookieId);
		}

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

		//Request to obtain class and enrollmentId
		$url = $magister->baseURL."/api/personen/".$magister->magisterId."/aanmeldingen?geenToekomstige=false&peildatum=".date("Y-m-d");
		$result =  $magister->get($url, $magister->cookieId);

		//Get and set class
		$class = $result->Items[0]->Groep->Omschrijving;
		$magister->profile->class = preg_replace('/Klas\s+/i', '', $class);

		//Get and set enrollmentId needed for fetching subjects and grades
		$magister->enrollmentId = $result->Items[0]->Id;
		$magister->session->put('enrollmentId', $magister->enrollmentId);

		return true;

	}


	public function getPicture($url=null, $cookieId=null){
		$magister = $this->master;

		//Filename will be just a simple md5 function to prevent script kiddies to bulk obtain profile pics
		$fileName = md5($magister->school.'-'.$magister->magisterId.$magister->fetchPictureSalt).'.jpeg';
		//Check if everything is ready to be downloaded
		if( isset($url) && isset($cookieId) ){

			$saveto = $magister->fetchPictureFolder.$fileName;
			//Download only if it isn't saved yet.
			if(!file_exists($saveto)){
				$magister = $this->master;

				$ch=curl_init();

				curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.6) Gecko/20070725 Firefox/2.0.0.6');
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
				curl_setopt($ch, CURLOPT_HEADER, false);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
				curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
				curl_setopt($ch, CURLOPT_TIMEOUT, 60);
				curl_setopt($ch, CURLOPT_REFERER, $magister->baseURL);

				if(isset($cookieId)){
					$cookieFile = $magister->cookieFolder.$cookieId.'.m6.cookie';
					curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
					curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
				}

				curl_setopt($ch, CURLOPT_URL, $url);
				curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
				curl_setopt($ch, CURLOPT_VERBOSE, $magister->debug);

				$result = curl_exec($ch);
				if(curl_errno($ch)){
					echo 'error:' . curl_error($ch);
				}
				$info = curl_getinfo($ch);
				
				$fp = fopen($saveto,'x');
				fwrite($fp, $result);
				fclose($fp);

				curl_close($ch);

				if(floor($info['http_code'] / 100) >= 4) {
					$result = json_decode($result);
					throw new Magister6_Error('Uh oh, something went wrong: ' . $result->Message);
				}
			}

			return true;

		} else {

			//If there are no parameters, just return the file name of the saved profile pic
			return $fileName;
		}


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