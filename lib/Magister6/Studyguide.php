<?php
require_once 'Attachment.php';

class Magister6_Studyguide {
	public function __construct(Magister6 $master) {
		$this->master = $master;
	}

	private $resultList;

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
		$subjectLong = $this->getSubjectName($result->VakCodes[0]);

		$contentList = array();

		foreach($result->Onderdelen->Items as $item) {

			//Yup another request to fetch Attachments ): 
			$attachmentList = array();
			$fetchAttachmentsUrl = $magister->baseURL."/api/leerlingen/".$magister->magisterId."/studiewijzers/".$studyguideId."/onderdelen/".$item->Id;
			$fetchAttachmentsResult = $magister->get($fetchAttachmentsUrl, $magister->cookieId);

			foreach($fetchAttachmentsResult->Bronnen as $attachmentItem) { 
				
				$attachmentList[] = new Attachment_Adapter($attachmentItem->Naam, $attachmentItem->BronSoort, $attachmentItem->Links[0]->Href);

			}

			$newContent = preg_replace("/font-size\s*?:\s*?[0-9]*px;?/i", "", $item->Omschrijving);

			$contentList[] = new StudyguideItem_Adapter($item->Titel, $newContent, $attachmentList);

		}

		return new StudyGuide_Adapter($result->Id, $startDate, $endDate, $result->Titel, $result->VakCodes[0], $subjectLong, $subjectType, $result->InLeerlingArchief, $contentList);

	}




	/*
		Grabbing every available studyguide.

	*/

	public function getList($subject=null) {
		$magister = $this->master;


		$result = $this->resultList;
		if( empty($result) ) {
			$url = $magister->baseURL."/api/leerlingen/".$magister->magisterId."/studiewijzers?peildatum=".date("Y-m-d");
			$result = $magister->get($url, $magister->cookieId);
		}

		$list = array();

		$fetchAll = true;
		if(!empty($subject)) $fetchAll = false;

		foreach($result->Items as $item){
			if($fetchAll || $item->VakCodes[0] == $subject) {

				$startDate = $magister->parseDateTime($item->Van);
				$endDate = $magister->parseDateTime($item->TotEnMet);
				$subjectType = $this->getSubjectType($item->VakCodes[0]);
				$subjectLong = $this->getSubjectName($item->VakCodes[0]);

				$list[] = new StudyguideList_Adapter($item->Titel, $item->Id, $startDate, $endDate, $item->VakCodes[0], $subjectLong, $subjectType, $item->InLeerlingArchief);
			}

		}

		return $list;

	}

	public function getSimpleList() {
		$magister = $this->master;

		$result = $this->resultList;
		if( empty($result) ) {
			$url = $magister->baseURL."/api/leerlingen/".$magister->magisterId."/studiewijzers?peildatum=".date("Y-m-d");
			$result = $magister->get($url, $magister->cookieId);
		}

		$studyguides = array();
		foreach($result->Items as $item) {

			$startDate = $magister->parseDateTime($item->Van);
			$endDate = $magister->parseDateTime($item->TotEnMet);
			preg_match("/(?:(?<=Periode)|(?<=Thema)|(?<=Kapitel)|(?<=OLE)|(?<=P)|(?<=T))[\s]?0*?[1-9]/i", $item->Titel, $parsedPeriod);
			$period = 0;
			if ( isset($parsedPeriod[0]) ) $period = (int) $parsedPeriod[0]; 

			$newer = false;

			//Check if defaultId is already set
			if( isset($studyguides[$item->VakCodes[0]]) ) {

				$studyguide = $studyguides[$item->VakCodes[0]];

				//Now check if this is a newer studyguide, a very simple algorithm 
				if( $period > $studyguide["period"] || ($item->InLeerlingArchief == false && $studyguide["archived"] == true) || ($startDate > $studyguide["startDate"] && $endDate > $studyguide["endDate"]) ) {
					$newer = true;
				}

			} else {
				$newer = true;
			}

			if ($newer) {
				$replaceStudyguide = array("period" => $period, "startDate" => $startDate, "endDate" => $endDate, "archived" => $item->InLeerlingArchief, "id" => $item->Id);
				$studyguides[$item->VakCodes[0]] = $replaceStudyguide;
			}

		}

		$simpleStudyguideList = array();

		foreach ($studyguides as $subject => $studyguide) {
			$subjectType = $this->getSubjectType($subject);
			$subjectLong = $this->getSubjectName($subject);
			$simpleStudyguideList[] = new StudyGuideSimpleList_Adapter($subject, $subjectLong, $subjectType, $studyguide["id"]);
		}
		
		return $simpleStudyguideList;

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
		$beta = array('na' => 1, 'sk' => 1, 'bi' => 1, 'wi' => 1, 'wa' => 1, 'wb' => 1, 'wc' => 1, 'wd' => 1, 'wisa' => 1, 'wisb' => 1, 'wisc' => 1, 'wisd' => 1, 'm&n' => 1, 'anw' => 1, 'rv' => 1);
		$gamma = array('lv' => 1, 'ma' => 1, 'ec' => 1, 'ak' => 1, 'gs' => 1, 'maw' => 1, 'mo' => 1, 'm&o' =>1, 'm&m' => 1);
		if (isset($alpha[$subject]))		$subjectType = 1;
		elseif (isset($beta[$subject]))		$subjectType = 2;
		elseif (isset($gamma[$subject]))	$subjectType = 3;
		else 								$subjectType = 4;
		
		return  $subjectType;

	}


	private function getSubjectName($subject) {

		$subjects = array("en", "fa", "du", "sp", "bi", "maw", "ma", "ec",  "is", "gs", "dr", "mu", "bv", 'be', "ckv", "la", "ku", "sk", "ak", "wi", "lv", "wa", "wb", "wc", "wd", "ne", "anw", "na", "mo", "m&o", "m&n", "m&m", "tl", "rv");
        $readableSubject = array("Engels", "Frans", "Duits", "Spaans", "Biologie", "Maatschappijwetenschappen", "Maatschappijleer", "Economie", "", "Geschiedenis", "Drama", "Muziek", "Beeldend", "Beeldend", "CKV", "Latijn", "", "Scheikunde", "Aardrijkskunde", "Wiskunde", "Levenbeschouwing", "Wiskunde A", "Wiskunde B", "Wiskunde C", "Wiskunde D", "Nederlands", "Algemene Natuurwetenschappen", "Natuurkunde", "Management & Organisatie", "Management & Organisatie", "Mens & Natuur", "Mens & Maatschappij", "", "Rekenvaardigheid");
        return str_replace($subjects, $readableSubject, $subject);

	}

}

/*
	Classes to reformat json output
*/

class Studyguide_Adapter {

	public $id, $startDate, $endDate, $title, $subject, $subjectLong, $subjectType, $archived, $content;
	public function __construct($i, $sd, $ed, $ti, $s, $sl, $st, $arch, $cont) {
		$this->id = $i;
		$this->startDate = $sd;
		$this->endDate = $ed;
		$this->title = $ti;
		$this->subject = $s;
		$this->subjectLong = $sl;
		$this->subjectType = $st;
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


class StudyGuideList_Adapter {
	public $title, $id, $startDate, $endDate, $subject, $subjectLong, $subjectType, $archived;
	public function __construct($ti, $i, $sd, $ed, $s, $sl, $st, $a){
		$this->title = $ti;
		$this->id = $i;
		$this->startDate = $sd;
		$this->endDate = $ed;
		$this->subject = $s;
		$this->subjectLong = $sl;
		$this->subjectType = $st;
		$this->archived = $a;
	}
}

class StudyGuideSimpleList_Adapter {
	public $subject, $subjectLong, $subjectType, $defaultId;
	public function __construct($s, $sl, $st, $id){
		$this->subject = $s;
		$this->subjectLong = $sl;
		$this->subjectType = $st;
		$this->defaultId = $id;
	}
}
?>