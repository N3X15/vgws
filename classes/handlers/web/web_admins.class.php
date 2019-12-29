<?php
use \VGWS\Content\AdminActionHandler;
use \VGWS\Content\Page;
//TODO: Make all this shit AJAX.
class UpdateAdminPermission extends AdminActionHandler
{
    protected $requiredFlags = R_PERMISSIONS;
    public function onRequest()
    {
        foreach ($_POST['flags'] as $ckey => $flags) {
            $admin = \VGWS\Auth\Admin::FindCKey($ckey);
            if (!$admin) {
                Page::Message('error', 'Unable to find that admin.');
                continue;
            }
            if (!$this->page->user->canEdit($admin)) {
                Page::Message('error', 'You cannot edit that admin.');
                continue;
            }
            $oldflags=$admin->Flags;
            $admin->Flags=0;
            foreach ($flags as $flag) {
                $admin->Flags |= intval($flag);
            }
            if ($admin->Flags != $oldflags) {
                $admin->Update();
            }
        }
        return '';
    }
}
class DeleteAdmin extends AdminActionHandler
{
    protected $requiredFlags = R_PERMISSIONS;
    public function onRequest()
    {
        var_dump($_POST['ckeys']);
        foreach ($_POST['ckeys'] as $ckey) {
            $admin = \VGWS\Auth\Admin::FindCKey($ckey);
            if (!$admin) {
                Page::Message('error', 'Unable to find that admin.');
                continue;
            }
            if (!$this->page->user->canEdit($admin)) {
                Page::Message('error', 'You cannot edit that admin.');
                continue;
            }
            $admin->Delete();
        }
        return '';
    }
}
class SetAdminRank extends AdminActionHandler
{
    protected $requiredFlags = R_PERMISSIONS;
    public function onRequest()
    {
        global $ADMIN_RANKS;
        $ckeys = $_POST['ckeys'];
        var_dump($_POST['ckeys']);
        var_dump($_POST['rank']);
        $rank = filter_input(INPUT_POST, 'rank', FILTER_SANITIZE_STRING);
        if (!array_key_exists($rank, $ADMIN_RANKS)) {
            Page::Message('error', 'Unable to find that rank.');
            return;
        }
        $newflags=$ADMIN_RANKS[$rank];
        foreach ($ckeys as $ckey) {
            $admin = Admin::FindCKey($ckey);
            if (!$admin) {
                Page::Message('error', 'Unable to find that admin.');
                continue;
            }
            if (!$this->page->user->canEdit($admin)) {
                Page::Message('error', 'You cannot edit that admin.');
                continue;
            }
            $admin->Flags=$newflags;
            $admin->Rank=$rank;
            $admin->Update();
        }

        return '';
    }
}

class AdminListPage extends Page
{
    public $relurl = '/admins';
    public $title = "Admins";
    public $image = "/img/admins.png";
    public function __construct()
    {
        parent::__construct();
        $this->RegisterAction('delete', new DeleteAdmin($this, false));
        $this->RegisterAction('setrank', new SetAdminRank($this, false));
        $this->RegisterAction('update', new UpdateAdminPermission($this, false));
    }
    public function OnBody()
    {
        global $ADMIN_FLAGS, $ADMIN_RANKS;
        $res = DB::Execute("SELECT * FROM erro_admin ORDER BY rank, ckey");
        $admins=[];
        foreach($res as $arow){
          $admins[]=Admin::FromRow($arow);
        }
        $this->setTemplateVar('admins', $admins);
        $this->setTemplateVar('ADMIN_FLAGS', $ADMIN_FLAGS);
        $this->setTemplateVar('ADMIN_RANKS', $ADMIN_RANKS);
        $this->setTemplateVar('isAdmin', $this->sess != null && ($this->sess->flags & R_PERMISSIONS) == R_PERMISSIONS);
        return $this->displayTemplate('web/admins');
    }
}

\VGWS\Router::Register('/admins/?', new AdminListPage());
