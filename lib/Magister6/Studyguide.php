<?php
require_once 'Attachment.php';

class Magister6_Studyguide {
	public function __construct(Magister6 $master) {
		$this->master = $master;
	}

	/*
		Get Studyguide content by providing an id.

	*/
	public function get($studyguideId) {
		$magister = $this->master;

		//Request
		$url = $magister->baseURL."/api/leerlingen/".$magister->magisterId."/studiewijzers/".$studyguideId;
		$result = $magister->get($url, $magister->cookieId);

		//Parse some variables
		$startDate = $magister->parseDateTime($result->Van);
		$endDate = $magister->parseDateTime($result->TotEnMet);
		$subjectType = $this->getSubjectType($result->VakCodes[0]);

		$contentList = array();

		foreach($result->Onderdelen->Items as $item) {

			//Yup another request to fetch Attachments ): 
			$attachmentList = array();
			$fetchAttachmentsUrl = $magister->baseURL."/api/leerlingen/".$magister->magisterId."/studiewijzers/".$studyguideId."/onderdelen/".$item->Id;
			$fetchAttachmentsResult = $magister->get($fetchAttachmentsUrl, $magister->cookieId);

			foreach($fetchAttachmentsResult->Bronnen as $attachmentItem) { 
				
				$attachmentList[] = new Attachment_Adapter($attachmentItem->Naam, $attachmentItem->BronSoort, $attachmentItem->Links[0]->Href);

			}

			$contentList[] = new StudyguideItem_Adapter($item->Titel, $item->Omschrijving, $attachmentList);

		}

		return new StudyGuide_Adapter($result->Id, $startDate, $endDate, $result->Titel, $result->VakCodes[0], $subjectType, $result->InLeerlingArchief, $contentList);

	}




	/*
		Grabbing every available studyguide.

	*/

	public function getList() {
		$magister = $this->master;

		$url = $magister->baseURL."/api/leerlingen/".$magister->magisterId."/studiewijzers?peildatum=".date("Y-m-d");

	}

	/* Some of my stupidness :P
		Helper Method to get the type of the subject
		$subjectType == 1 is a language
		$subjectType == 2 is a beta subject like physics and mathematics
		$subjectType == 3 is a gamma subject like history and economics
		$subjectType == 4 is a just any other subject which doesn't match any of these above.
	*/

	private function getSubjectType($subject) {

		$alpha = array('netl' => 1, 'entl' => 1, 'dutl' => 1, 'fatl' => 1, 'sptl' => 1, 'latl' => 1, 'sp' => 1, 'la' => 1, 'ne' => 1, 'en' => 1, 'du' => 1, 'fa' => 1);
		$beta = array('na' => 1, 'sk' => 1, 'bi' => 1, 'wi' => 1, 'wa' => 1, 'wb' => 1, 'wc' => 1, 'wd' => 1, 'wisa' => 1, 'wisb' => 1, 'wisc' => 1, 'wisd' => 1, 'm&n' => 1, 'anw' => 1);
		$gamma = array('lv' => 1, 'ma' => 1, 'ec' => 1, 'ak' => 1, 'gs' => 1, 'maw' => 1, 'mo' => 1, 'm&o' =>1, 'm&m' => 1);
		if (isset($alpha[$subject]))		$subjectType = 1;
		elseif (isset($beta[$subject]))		$subjectType = 2;
		elseif (isset($gamma[$subject]))	$subjectType = 3;
		else 								$subjectType = 4;
		
		return  $subjectType;

	}

}

/*
	Classes to reformat json output
*/

class Studyguide_Adapter {

	public $id, $startDate, $endDate, $title, $subject, $type, $archived, $content;
	public function __construct($i, $sd, $ed, $ti, $s, $t, $arch, $cont) {
		$this->id = $i;
		$this->startDate = $sd;
		$this->endDate = $ed;
		$this->title = $ti;
		$this->subject = $s;
		$this->type = $t;
		$this->archived = $arch;
		$this->content = $cont;
	}

}

class StudyguideItem_Adapter {

	public $title, $content, $attachments;
	public function __construct($ti,$co,$at){
		$this->title=$ti;
		$this->content=$co;
		$this->attachments=$at;
	}

}
?>