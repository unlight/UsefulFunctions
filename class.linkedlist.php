<?php

/* Garden hooks example.

$AllowSend = TRUE;
$this->EventArguments['AllowSend'] =& $AllowSend;
$this->FireEvent('BeforeSend');
if ($AllowSend) $this->Send();

Disadvantages:
1. The final word rests for the latest event handler

Linked list:
if ($this->AllowSend() && ($this->Next != False && $this->Next->AllowSend()) $this->Send()

*/

class LinkedList extends SplDoublyLinkedList {
	
	public $Name;
	
}