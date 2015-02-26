<?php
class Attachment_Adapter {

	public $title,$type,$url;
	public function __construct($ti,$ty,$ur) {

		$this->title=$ti;

		/* 
	
		HELPER METHOD TO PARSE ATTACHMENT URL and set the type of the attachment

		If it is a real attachment (not a website/ youtube video) 
		then this dirty method below will strip the attachment url to the last part
		because of the first /Api/Leerlingen/STUDENTID part of the url is different for every user
		which can cause problems if you want to store it to a database.
		when you want to display the attachment, you must add the first part of the url on your own. See the example.
		$type==1 = Just a normal attachment, $type == 2 = An assignment, $type == 3 = A website, $type == 4 = A Youtube Video.

		*/

		$attachmentUrl = $ur;
		$attachmentType = $ty;
		
		if(empty($ur) || strpos($ur,'/api/')!== false){$attachmentUrl=preg_replace('/\/api\/leerlingen\/[0-9]+/i', '', $ur); $attachmentType=1;}elseif(strpos($ur,'youtube.com/watch?v=')!== false){$attachmentType=4;}else{$attachmentType=3;}

		$this->type=$attachmentType;
		$this->url=$attachmentUrl;

	}


}

?>