<?php
require_once 'Attachment.php';

class Magister6_Assignment {
	public function __construct(Magister6 $master) {
		$this->master = $master;
	}

	public function get($id) {
		$magister = $this->master;

		$url = $magister->baseURL."/api/personen/".$magister->magisterId."/opdrachten/".$id;
		$result = $magister->get($url, $magister->cookieId);

		$deadline = $magister->parseDateTime($result->InleverenVoor);
		$submittedDate = $magister->parseDateTime($result->IngeleverdOp);

		$attachmentList = array();
		foreach($result->Bijlagen as $attachmentItem) { 
		
			$attachmentList[] = new Attachment_Adapter($attachmentItem->Naam, $attachmentItem->BronSoort, $attachmentItem->Links[0]->Href);

		}

		$submittedAssignmentsList = array();
		foreach ($result->VersieNavigatieItems as $submittedAssignment) {
		
			$fetchSubmmitedAssignmentUrl = $magister->baseURL.$submittedAssignment->Links[0]->Href;
			$submittedAssignment = $magister->get($fetchSubmmitedAssignmentUrl, $magister->cookieId);
		
			$date = $magister->parseDateTime($submittedAssignment->IngeleverdOp);

			$submittedAssignmentAttachmentList = array();
			foreach ($submittedAssignment->LeerlingBijlagen as $attachmentItem) {
				$submittedAssignmentAttachmentList[] = new Attachment_Adapter($attachmentItem->Naam, $attachmentItem->BronSoort, $attachmentItem->Links[0]->Href);
			}

			$submittedAssignmentFeedbackAttachmentList = array();
			foreach ($submittedAssignment->FeedbackBijlagen as $attachmentItem) {
				$submittedAssignmentFeedbackAttachmentList[] = new Attachment_Adapter($attachmentItem->Naam, $attachmentItem->BronSoort, $attachmentItem->Links[0]->Href);
			}

			$submittedAssignmentsList[] = new SubmittedAssignment_Adapter($date, $submittedAssignment->Beoordeling, $submittedAssignment->LeerlingOpmerking, $submittedAssignment->DocentOpmerking, $submittedAssignmentAttachmentList, $submittedAssignmentFeedbackAttachmentList);

		}

		return new Assignment_Adapter($result->Titel, $result->Id, $result->Vak, $result->Omschrijving, $result->Docenten[0]->Naam, $deadline, $submittedDate, $result->Beoordeling, $attachmentList, $submittedAssignmentsList);

	}


	public function getList($subject=null) {
		$magister = $this->master;

		$url = $magister->baseURL."/api/personen/".$magister->magisterId."/opdrachten?skip=0&top=25&einddatum=2015-07-31&startdatum=2014-09-25&status=alle";
		$result = $magister->get($url, $magister->cookieId);

		$fetchSubjectOnly = false;
		if(!empty($subject)) $fetchSubjectOnly = true;

		$list = array();

		if($fetchSubjectOnly) {
			foreach($result->Items as $item){
				if($item->Vak == $subject){
					$list[] = $this->get($item->Id);
				}
			}
		} else {
			foreach($result->Items as $item){
				$deadline = $magister->parseDateTime($item->InleverenVoor);
				$submittedDate = $magister->parseDateTime($item->IngeleverdOp);

				$list[] = new Assignment_Adapter($item->Titel, $item->Id, $item->vak, $item->Omschrijving, NULL, $deadline, $submittedDate, NULL, NULL, NULL);
			}

		}

		return $list;

	}
}




class Assignment_Adapter {
	public $title, $id, $subject, $description, $teacherName, $deadline, $submittedDate, $mark, $attachments, $submittedAssignments;
	public function __construct($ti, $i, $sub, $desc, $tn=null, $dl, $sd, $m=null, $at=null, $sba=null){
		$this->title = $ti;
		$this->id = $i;
		$this->subject = $sub;
		$this->description = $desc;
		if(!empty($tn)) $this->teacherName = $tn;
		$this->deadline = $dl;
		$this->submittedDate = $sd;
		if(!empty($m)) $this->mark = $m;
		if(!empty($at)) $this->attachments = $at;
		if(!empty($sba)) $this->submittedAssignments = $sba;
	}
}

class SubmittedAssignment_Adapter {
	public $date, $mark, $noteStudent, $noteTeacher, $attachments, $feedbackAttachments;
	public function __construct($d, $m, $ns, $nt, $at, $fbat){
		$this->date = $d;
		$this->mark = $m;
		$this->noteStudent = $ns;
		$this->noteTeacher = $nt;
		$this->attachments = $at;
		$this->feedbackAttachments = $fbat;
	}
}

?>