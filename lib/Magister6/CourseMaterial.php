<?php
class Magister6_CourseMaterial {
	public function __construct(Magister6 $master) {
		$this->master = $master;
	}

	public function get($subject=null){
		$magister = $this->master;

		//Request
		$url = $magister->baseURL."/api/personen/".$magister->magisterId."/lesmateriaal?DigitaalLicentieFilter=0";
		$result = $magister->get($url, $magister->cookieId);

		if(empty($subject)) {

			$list = array();

			foreach($result->Items as $item){

				$list[] = new CourseMaterial_Adapter($item->Titel, $item->Vak->Afkorting, $item->Url);

			}

			return $list;

		} else {
			foreach($result->Items as $item){
				if($item->Vak->Afkorting == $subject) {
					return new CourseMaterial_Adapter($item->Titel, $subject, $item->Url);
				}
			}
		}


	}

	public function getMaterial($subject) {



	}

}


class CourseMaterial_Adapter {

	public $title, $subject, $url;
	public function __construct($ti, $s, $ur){
		$this->title = $ti;
		$this->subject = $s;
		$this->url = $ur;
	}

}


?>