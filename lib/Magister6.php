<?php
date_default_timezone_set('Europe/Amsterdam');

require_once 'Session.php';
require_once 'Magister6/Studyguide.php';
require_once 'Magister6/UserData.php';

class Magister6 {

	public $school, $baseURL, $magisterId, $cookieId, $profile;
	public $schoolName; //Defined by the getSchoolName() method
	public $cookieFolder = '/tmp/'; //Save the Magister6 cookies to this folder. N.B.: PHP need write access to this directory! 
	public $debug = false;
	public $session;
	private $sessionName = 'M6-APP_SESSION'; //Name of OUR session cookie, users could see this cookie title
	private $internalSessionName = 'M6-APP'; //Internal name of OUR session cookie, it must not conflict with other PHP apps.

	/*
		Construct function will be used to setup CURL 
		Parameters are ONLY required to login otherwise it will try to use session data.
	*/
	public function __construct($schl=null, $username=null, $password=null){

		$this->initSession();

		//Set up for the userData method to obtain required user data and returns a class for the profile variable
		$this->userData = new Magister6_UserData($this);

		//First we will check if the are parameters given in order to login
		if( isset($schl) && isset($username) && isset($password) ){

			//Setting some variables
			$this->cookieId = md5($_SERVER['REMOTE_ADDR'].round(microtime(true) * 1000).mt_rand(0,1000)); //generate unique cookie ID
			$this->baseURL = "https://$schl.magister.net";
			$this->school = $schl;

			$loginUrl = $this->baseURL."/api/sessie";
			$postData = array('Gebruikersnaam' => $username, 'Wachtwoord' => $password, "IngelogdBlijven" => true, "GebruikersnaamOnthouden" => true);
			
			//The actual logging in post request
			$this->post($loginUrl, $postData, $this->cookieId);

			//Get some required user info and pass the $username into it too
			$this->profile = $this->userData->get($username);

			//Store everything in a session
			$this->saveToSession();

		} else {

			//If no construct parameters are found, then retrieve session
			$this->retrieveFromSession();

		}

		/* 
			Finally, extend this Magister6 class with other class methods
		*/

		$this->studyguide = new Magister6_Studyguide($this);

	}

	/*
		Get the full name of the school
	*/

	public function getSchoolName() {

		$url = $this->baseURL."/api/schoolconfiguratie";
		$result = $this->get($url, $this->cookieId);
		return $result->Licentie;

	}

	/* HELPER METHODS:
	
		CURL GET REQUEST 
	*/

	public function get($url, $cookieId=null){

		$ch=curl_init();

		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.6) Gecko/20070725 Firefox/2.0.0.6');
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($ch, CURLOPT_TIMEOUT, 60);
		curl_setopt($ch, CURLOPT_REFERER, $this->baseURL);

		if(isset($cookieId)){
			$cookieFile = $this->cookieFolder.$cookieId.'.txt';
			curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
			curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
		}

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_VERBOSE, $this->debug);

		$result = curl_exec($ch);
		curl_close($ch);

		return json_decode($result);


	}

	/*
		CURL POST METHOD
	*/

	public function post($url, $postData, $cookieId=null){

		$postData = json_encode($postData, true);

		$ch=curl_init();

		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.6) Gecko/20070725 Firefox/2.0.0.6');
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
		curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($ch, CURLOPT_TIMEOUT, 60);
		curl_setopt($ch, CURLOPT_REFERER, $this->baseURL);

		if(isset($cookieId)){
			$cookieFile = $this->cookieFolder.$cookieId.'.txt';
			curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
			curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
		}

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'charset=UTF-8'));
		curl_setopt($ch, CURLOPT_VERBOSE, $this->debug);

		$result = curl_exec($ch);
		if(curl_errno($ch)){
			echo 'error:' . curl_error($ch);
		}
		curl_close($ch);

		return true;


	}

	/*
		INIT USER'S SESSION
	*/

	private function initSession(){

		$this->session = new SecureSessionHandler($this->internalSessionName, $this->sessionName);
		$this->session->start();

		/*
			Check if the session is EOL or if the user is violating one of the security mechanisms defined in the SecureSessionHandler. 
		*/
		if ( ! $this->session->isValid(5)) {

			$cookieId = $session->get('cookieId');
			if (!empty($cookieId)) unlink($cookieFolder.$cookieId.'.txt');
			$this->session->destroy();

		}

	}

	/*
		SAVE TO SESSION METHOD
	*/

	private function saveToSession() {

		$session = $this->session;

		$session->put('school', $this->school);
		$session->put('baseURL', $this->baseURL);
		$session->put('magisterId', $this->magisterId);
		$session->put('cookieId', $this->cookieId);
		
		$session->put('profile', $this->profile);

	}

	/*
		RETRIEVE FROM SESSION METHOD
	*/

	private function retrieveFromSession() {

		$this->school = $this->session->get('school');
		$this->baseURL = $this->session->get('baseURL');
		$this->magisterId = $this->session->get('magisterId');
		$this->cookieId = $this->session->get('cookieId');

		$this->profile = $this->session->get('profile');


	}

	/*
		PARSE MAGISTER6 DATE AND TIME STRINGS METHOD
	*/

	public function parseDateTime($string) {

		$dateTime = date_create($string,timezone_open("UTC"));
		date_timezone_set($dateTime,timezone_open(date_default_timezone_get()));

		return $dateTime;

	}

}
?>