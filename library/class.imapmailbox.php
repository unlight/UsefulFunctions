<?php
require_once USEFULFUNCTIONS_VENDORS . '/IMAPv2.php';

class ImapMailbox extends Gdn_Pluggable {
	
	public $IMAP2;
	public $Connection;
	public $CheckResult;
	
	public function __construct($Host, $Options, $Login, $Password) {
		LoadExtension('imap', True);
		$MailBox = '{'.$Host.':'.$Options.'}INBOX';
		$this->Connection = imap_open($MailBox, $Login, $Password, OP_SILENT);
		$this->IMAP2 = new Mail_IMAPv2($this->Connection);
		parent::__construct();
	}
	
	public function Close() {
		//$this->IMAP2->setOptions('close', CL_EXPUNGE);
		$this->IMAP2->Expunge();
		$this->IMAP2->Close();
		imap_errors(); // Clear error stack.
	}
	
	public function MessageCount() {
		//return $this->IMAP2->MessageCount();
		$MessagesCount = $this->Check('Nmsgs');
		return $MessagesCount;
	}
	
	
	
	/* Date - current system time formatted according to RFC2822 
	Driver - protocol used to access this mailbox: POP3, IMAP, NNTP 
	Mailbox - the mailbox name 
	Nmsgs - number of messages in the mailbox 
	Recent - number of recent messages in the mailbox */
	public function Check($Name = '') {
		$this->CheckResult = imap_check($this->Connection);
		return ($Name != '') ? ObjectValue($Name, $this->CheckResult) : $this->CheckResult;
	}
	
	public function GetMessage($N) {
		$ImapMessage = new ImapMessage($N, $this);
		return $ImapMessage;
	}
	
	public function GetNum($N) { // deprecated
		$ImapMessage = new ImapMessage($N, $this);
		return $ImapMessage;
	}
	
	public function GetUnseenEmails() {
		return $this->Search('UNSEEN');
	}
	
	public function Search($Flag) {
		return imap_search($this->Connection, $Flag);
	}



}


class ImapMessage{
	
	public $Subject;
	public $SenderMail;
	public $SenderName;
	public $BodyText;
	public $Attachs = array();
	
	private $Number;
	private $Uid;
	
	public $Mailbox;
	
	public function __construct($Number, &$Mailbox) {
		$this->Mailbox =& $Mailbox;
		$Connection =& $this->Mailbox->Connection;
		$Tmp = imap_fetch_overview($Connection, $Number);
		$this->Overview = $Tmp[0];
		$this->Structure = imap_fetchstructure($Connection, $Number);
		$this->HeaderInfo = imap_headerinfo($Connection, $Number);
		
		$this->MailDateTime = Gdn_Format::ToDateTime( $this->HeaderInfo->udate );
		
		$this->Number = $this->Overview->msgno;
		$this->Uid = $this->Overview->uid;
		
		$this->RetrieveInfo();
		$this->RetrieveBodyText();
		$this->RetrieveAttachments();
		return $this;
	}
	
	public function RetrieveAttachments() {
		$Parts = $this->Mailbox->IMAP2->getParts($this->Number, '0', True, array('retrieve_all' => True));

		$InlineParts = @$Parts['in']['ftype'];
		$AttachmentsParts = @$Parts['at']['ftype'];
		$Parts = array_merge((array)$InlineParts, (array)$AttachmentsParts);
		
		if(Count($Parts) == 0) return False;
		
		$Mimes = array();
		
		foreach($Parts as $MimeType) if(preg_match('#^(application|image|video)/#', $MimeType)) $Mimes[] = $MimeType;
		$Pids = $this->Mailbox->IMAP2->extractMIME($this->Number, array_unique($Mimes));
		if(!$Pids) return False;
		$TempFolder = realpath(sys_get_temp_dir());
		foreach($Pids as $Pid) {
			$Body = $this->Mailbox->IMAP2->getBody($this->Number, $Pid);
			$Dummy = new StdClass();
			$Dummy->MimeType = $Body['ftype'];
			
			$Dummy->FileName = self::DecodeMimeString($Body['fname']);
			$Dummy->TempFilePath = GenerateCleanTargetName($TempFolder, $Dummy->FileName);
			file_put_contents($Dummy->TempFilePath, $Body['message']);
			$this->Attachs[] = $Dummy;
		}
		return $this;
	}
	
	public function RetrieveInfo() {
		$this->Subject = self::DecodeMimeString( ObjectValue('subject', $this->Overview) );
		$From = $this->HeaderInfo->from[0];
		$this->SenderMail = strtolower($From->mailbox.'@'.$From->host);
		if(property_exists($From, 'personal')) $this->SenderName = self::DecodeMimeString($From->personal);
		return $this;
	}
	
	public function RetrieveBodyText() {
		$Body = $this->Mailbox->IMAP2->getBody($this->Number);
		//$RawMessage = $this->Mailbox->IMAP2->getRawMessage($this->Number);
		//$BodyTextPlain = $this->Mailbox->IMAP2->getBody($this->Number, 1, 2);
		//$Parts = $this->Mailbox->IMAP2->getParts($this->Number, '0', True, array('retrieve_all' => True));
		if($Body == False) $Body = $this->Mailbox->IMAP2->getBody($this->Number, '1.1');
		//d($this->Number, $Body, $this->Mailbox->IMAP2->getBody($this->Number, '1.1'));
		
		//if(!$Body) throw new Exception('Body is none.');
		if (!$Body) {
			$this->BodyText = '';
			return $this;
		}
			
		
		//$BodyTextHtml = $this->Mailbox->IMAP2->getBody($this->Number, '1.2');
		
		$Charset = strtolower($Body['charset']);
		$Text = strip_tags($Body['message']);
		if($Charset != '' && $Charset != 'utf-8') $Text = mb_convert_encoding($Text, 'utf-8', $Charset);
		// remove quoted text
		//$Text = preg_replace('/^\>+.*/m', '', $Text);
		//$Text = preg_replace('/\n.*$/s', '', $Text);
		//preg_match('/(\n+)(.+)$/s', $Text, $M);
		
		$Text = str_replace("\r", '', $Text);
		$Text = str_replace('&nbsp;', ' ', $Text);
		
		$this->BodyText = Trim($Text);
		return $this;
	}
	
	public static function DecodeMimeString($String) {
		if (!$String) return '';
		$Headers = imap_mime_header_decode($String);
		$Count = Count($Headers);
		$Return = '';
		for ($i = 0; $i < $Count; $i++) {
			$Charset = $Headers[$i]->charset;
			$Text = $Headers[$i]->text;
			//if($Charset == 'default') $Charset = 'ISO-8859-1';
			if($Charset == 'default') $Charset = 'US-ASCII';
			$Return .= mb_convert_encoding($Text, 'UTF-8', $Charset);
		}
		return $Return;
	}
	
	public function MarkAsFlagged() {
		imap_setflag_full($this->Mailbox->Connection, $this->Number, "\\Flagged");
		return $this;
	}
	
	public function MarkAsSeen() {
		imap_setflag_full($this->Mailbox->Connection, $this->Number, "\\Seen");
		return $this;
	}
	
	public function MarkAsDeleted() {
		imap_setflag_full($this->Mailbox->Connection, $this->Number, "\\Deleted");
		return $this;
	}
	
	public function Delete() {
		return $this->Mailbox->IMAP2->Delete($this->Number);
	}
	
	// todo: need remove this
	public function ReportCompleteTask($Name = '') {
		$Email = new Gdn_Email();
		$Email
			->To($this->SenderMail, $this->SenderName)
			->Subject('[Готово] '. $this->Subject)
			->Message('Ваша задача успешно выполнена: ' . $Name)
			->Send();
		$this->Delete();
		return $this;
	}
	
	public function ReturEmailToSender($ErrorText = '') {
		$Email = new Gdn_Email();
		$Message = LocalizedMessage('ReturEmailToSender %1$s %2$s %3$s', 
			$this->Subject, $this->BodyText, $ErrorText);
		$Email
			->To($this->SenderMail, $this->SenderName)
			->Subject('[Ошибка] '. $this->Subject)
			->Message($Message)
			->Send();
		$this->Delete();
		return $this;
	}
	
	
}


