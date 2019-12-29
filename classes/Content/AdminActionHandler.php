<?php

namespace VGWS\Content;

class AdminActionHandler extends ActionHandler {
   protected $requiredFlags = R_ADMIN;
   protected function RequireFlags($flags) {
       if($this->page->sess!=false)
           return false;
       return ($this->page->sess->flags & $flags) == $flags;
   }

   public function CanAccess() {
       return $this->RequireFlags($this->requiredFlags);
   }
}
