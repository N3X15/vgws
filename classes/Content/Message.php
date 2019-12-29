<?php
namespace VGWS\Content;
/**
* Validation/error message passed to a field or the entire page.
* @package vgstation-13
* @subpackage Pages
* @author Rob Nelson <nexisentertainment@gmail.com>
*/
class Message
{
   /**
    * Message itself.
    */
   public $message = '';

   /**
    * Severity of the message.
    * error, warning, or generic
    */
   public $severity = '';

   public function __construct($severity, $message)
   {
       $this->message = $message;
       $this->severity = $severity;
   }

}
