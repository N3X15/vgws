<?php

use \VGWS\Content\AdminActionHandler;
use \VGWS\Content\Page;
use \Atera\DB;

class RapsheetPage extends Page
{
    public $relurl = '/rapsheet';
    public $title = "Rapsheet";
    public $image = "/img/admins.png";
    public $adminOnly = true;

    public function OnBody()
    {
        global $ADMIN_FLAGS;
        $types=array(
            'PERMABAN',
            'TEMPBAN',
            'JOB_PERMABAN',
            'JOB_TEMPBAN',
            'APPEARANCE'
        );

        $pd = DB::Execute("SELECT * FROM erro_player WHERE ckey=? ORDER BY ckey", array($_REQUEST['ckey']));
        if (!$pd) {
            SQLError($db->ErrorMsg());
        }
        $this->setTemplateVar('playerdata', $pd);


        $banres = DB::Execute("SELECT * FROM erro_ban WHERE ckey=? ORDER BY ckey", array($_REQUEST['ckey']));
        if (!$banres) {
            SQLError($db->ErrorMsg());
        }
        $jbans = array();
        $bans = array();
        foreach ($banres as $row) {
            $key = md5($row['ckey'] . $row['reason']);
            $row['ban_active']=false;
            switch ($row['bantype']) {
                case 'JOB_TEMPBAN':
                case 'TEMPBAN':
                case 'CLUWNE':
                case 'APPEARANCE':
                    $row['expiration_time_php'] = strtotime($row['expiration_time']);
                    $row['ban_active'] = $row['expiration_time_php'] > time();
                    break;
                default:
                    $row['expiration_time'] = 'PERMANENT';
                    $row['ban_active']=true;
                    break;
            }
            if (is_null($row['ban_active'])) {
                $row['ban_active']=false;
            }
            if ($row['expiration_time'] == 'PERMANENT' || $row['expiration_time_php'] > time()) {
                switch ($row['bantype']) {
                    case 'JOB_PERMABAN':
                    case 'JOB_TEMPBAN':
                        if (!array_key_exists($key, $jbans)) {
                            $jbans[$key] = $row;
                            $jbans[$key]['job'] = array($row['job']);
                            $jbans[$key]['id'] = array($row['id']);
                        } else {
                            $jbans[$key]['job'][] = $row['job'];
                            $jbans[$key]['id'][] = $row['id'];
                        }
                        break;
                    case 'PERMABAN':
                    case 'TEMPBAN':
                    case 'CLUWNE':
                    case 'APPEARANCE':
                        if (!array_key_exists($key, $bans)) {
                            $bans[$key] = $row;
                            $bans[$key]['job'] = array($row['job']);
                        } else {
                            $bans[$key][] = $row['job'];
                        }
                        break;
                }
            }
        }

        $this->js_assignments['API_TARGET'] = fmtAPIURL('findcid');
        $this->js_assignments['AUTOCOMPLETE'] = \VGWS\Jobs\Jobs::GetAllKnownJobs();
        $this->scripts[] = \VGWS\Content\Assets::Get('js/rapsheet.min.js');

        $this->setTemplateVar('bans', $bans);
        $this->setTemplateVar('jbans', $jbans);
        $this->setTemplateVar('bantypes', $types);
        $this->setTemplateVar('ckey', $_REQUEST['ckey']);
        return $this->displayTemplate('web/rapsheet');
    }
}

\VGWS\Router::Register('/rapsheet/?', new RapsheetPage);
