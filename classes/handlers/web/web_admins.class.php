<?php
class UpdateAdminPermission extends AdminActionHandler
{
    protected $requiredFlags = R_PERMISSIONS;
    public function onRequest()
    {
        $ckey = filter_input(INPUT_POST, 'ckey', FILTER_SANITIZE_STRING);
        $admin = Admin::FindCKey($ckey);
        if(!$admin) {
            Page::Message('error', 'Unable to find that admin.');
            return;
        }
        if(!$this->page->user->canEdit($admin)) {
            Page::Message('error', 'You cannot edit that admin.');
            return;
        }
        $admin->Flags=0;
        foreach($_POST['flag'] as $flag) {
            $admin->Flags |= intval($flag);
        }
        $admin->Update();
        
        return '';
    }

}

class admins_handler extends Page {
	public $parent = '/';
	public $title = "Admins";
	public $image = "/img/admins.png";
    public function __construct() {
        parent::__construct();
        #$this->RegisterAction('add', new Manage_Boards_AddAction($this));
        #$this->RegisterAction('del', new Manage_Boards_DelAction($this));
        $this->RegisterAction('update', new UpdateAdminPermission($this,false));
    }
	public function OnBody() {
        global $ADMIN_FLAGS;
		$res = DB::Execute("SELECT * FROM erro_admin ORDER BY rank, ckey");
		$this->setTemplateVar('admins',$res);
        $this->setTemplateVar('ADMIN_FLAGS',$ADMIN_FLAGS);
        $this->setTemplateVar('isAdmin',$this->sess != null && ($this->sess->flags & R_PERMISSIONS) == R_PERMISSIONS);
		return $this->displayTemplate('web/admins.tpl.php');
	}
}

Page::Register('web_admins', new admins_handler);