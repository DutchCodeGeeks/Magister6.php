<?php


//Coming soon...




class Assignment{
	public $title,$id,$subject,$description,$teacherName,$lastDate,$submittedDate,$mark,$attachments,$submittedAssignments;
	public function __construct($ti,$i,$sub,$desc,$tN,$lD,$sD,$m,$at,$sbAs){
		$this->title = $ti;
		$this->id = $i;
		$this->subject = $sub;
		$this->description = $desc;
		$this->teacherName = $tN;
		$this->lastDate = $lD;
		$this->submittedDate = $sD;
		$this->mark = $m;
		$this->attachments = $at;
		$this->submittedAssignments = $sbAs;
	}
}

class SubmittedAssignment{
	public $date,$mark,$noteStudent,$noteTeacher,$attachments,$feedbackAttachments;
	public function __construct($d,$m,$nS,$nT,$at,$fbAt){
		$this->date = $d;
		$this->mark = $m;
		$this->noteStudent = $nS;
		$this->noteTeacher = $nT;
		$this->attachments = $at;
		$this->feedbackAttachments = $fbAt;
	}
}

?>