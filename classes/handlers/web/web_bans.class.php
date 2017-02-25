<?php

class BanListPage extends Page {
    public $relurl = '/bans';
    public $title = "Bans";
    public $image = "/img/bans.png";

    public function OnBody() {
        global $ADMIN_FLAGS;
        $types = array('PERMABAN', 'TEMPBAN', 'JOB_PERMABAN', 'JOB_TEMPBAN');

        if (count($_POST) > 0 && $this->sess != false) {

            //var_dump($_POST);
            /*
             'banType' => string '0' (length=1)
             'banCKey' => string 'n3x15' (length=5)
             'banIP' => string '50.47.189.146' (length=13)
             'banCID' => string 'not4u' (length=5)
             'banReason' => string 'BEING A BADMIN' (length=14)
             'banDuration' => string '0' (length=1)

             `id` int(11) NOT NULL AUTO_INCREMENT,
             `bantime` datetime NOT NULL,
             `serverip` varchar(32) NOT NULL,
             `bantype` varchar(32) NOT NULL,
             `reason` text NOT NULL,
             `job` varchar(32) DEFAULT NULL,
             `duration` int(11) NOT NULL,
             `rounds` int(11) DEFAULT NULL,
             `expiration_time` datetime NOT NULL,
             `ckey` varchar(32) NOT NULL,
             `computerid` varchar(32) NOT NULL,
             `ip` varchar(32) NOT NULL,
             `a_ckey` varchar(32) NOT NULL,
             `a_computerid` varchar(32) NOT NULL,
             `a_ip` varchar(32) NOT NULL,
             `who` text NOT NULL,
             `adminwho` text NOT NULL,
             `edits` text,
             `unbanned` tinyint(1) DEFAULT NULL,
             `unbanned_datetime` datetime DEFAULT NULL,
             `unbanned_ckey` varchar(32) DEFAULT NULL,
             `unbanned_computerid` varchar(32) DEFAULT NULL,
             `unbanned_ip` varchar(32) DEFAULT NULL,
             */
            if (array_key_exists('unban', $_POST)) {
                if($this->sess!=false)
                foreach (explode(',',$_POST['unban']) as $id)
                    DB::Execute('DELETE FROM erro_ban WHERE id=?', array(intval($id)));

            }
            if (array_key_exists('banType', $_POST)) {
                $ban = array();
                $ban['type'] = $types[intval($_POST['banType'])];
                $ban['ckey'] = $_POST['banCKey'];
                $ban['reason'] = $_POST['banReason'];
                $ban['ip'] = $_POST['banIP'];
                $ban['cid'] = intval($_POST['banCID']);
                $ban['duration'] = intval($_POST['banDuration']);
                $ban['job'] = $_POST['jobs'];
                $sql = <<<SQL
INSERT INTO
	erro_ban
SET
	bantime=NOW(),
	serverip='[Website Panel]',
	bantype=?,
	reason=?,
	job=?,
	duration=?,
	rounds=1,
	expiration_time=DATE_ADD(NOW(), INTERVAL ? MINUTE),
	ckey=?,
	computerid=?,
	ip=?,
	a_ckey=?,
	a_computerid=?,
	a_ip=?,
	who='LOLIDK',
	adminwho='LOLIDK'
SQL;
                if ($ban['type'] == 'JOB_TEMPBAN' || $ban['type'] == 'JOB_PERMABAN') {
                    foreach ($ban['job'] as $job) {
                        $args = array($ban['type'], $ban['reason'], $job, $ban['duration'], $ban['duration'], $ban['ckey'], $ban['cid'], $ban['ip'], $this->sess->ckey, '', $_SERVER['REMOTE_ADDR'], );
                        DB::Execute($sql, $args);
                    }
                } else {
                    $args = array($ban['type'], $ban['reason'], '', $ban['duration'], $ban['duration'], $ban['ckey'], $ban['cid'], $ban['ip'], $this->sess->ckey, '', $_SERVER['REMOTE_ADDR'], );

                    DB::Execute($sql, $args);
                }
            }
        }

        //$db->debug=true;
        $res = DB::Execute("SELECT * FROM erro_ban
		WHERE
			(
				bantype IN ('PERMABAN','JOB_PERMABAN')
				OR
				(
					bantype IN ('TEMPBAN','JOB_TEMPBAN','APPEARANCE')
					AND expiration_time > Now()
				)
			)
			AND isnull(unbanned)
		ORDER BY ckey");
        if (!$res)
            SQLError(DB::ErrorMsg());
        /*
          0 => string 'id' (length=2)
          1 => string 'bantime' (length=7)
          2 => string 'serverip' (length=8)
          3 => string 'bantype' (length=7)
          4 => string 'reason' (length=6)
          5 => string 'job' (length=3)
          6 => string 'duration' (length=8)
          7 => string 'rounds' (length=6)
          8 => string 'expiration_time' (length=15)
          9 => string 'ckey' (length=4)
          10 => string 'computerid' (length=10)
          11 => string 'ip' (length=2)
          12 => string 'a_ckey' (length=6)
          13 => string 'a_computerid' (length=12)
          14 => string 'a_ip' (length=4)
          15 => string 'who' (length=3)
          16 => string 'adminwho' (length=8)
          17 => string 'edits' (length=5)
          18 => string 'unbanned' (length=8)
          19 => string 'unbanned_datetime' (length=17)
          20 => string 'unbanned_ckey' (length=13)
          21 => string 'unbanned_computerid' (length=19)
          22 => string 'unbanned_ip' (length=11)
         */
        $jbans=array();
        $bans=array();
        foreach($res as $row) {
        	$key=md5($row['ckey'].$row['reason']);
        	switch($row['bantype']) {
        		case 'JOB_TEMPBAN':
        		case 'TEMPBAN':
        		case 'CLUWNE':
            case 'APPEARANCE':
        			$row['expiration_time_php']=strtotime($row['expiration_time']);
        			break;
        		default:
        			$row['expiration_time'] = 'PERMANENT';
        			break;
        	}
        	if($row['expiration_time']=='PERMANENT' || $row['expiration_time_php']>time())
        	{
        		switch($row['bantype']) {
        			case 'JOB_PERMABAN':
        			case 'JOB_TEMPBAN':
        				if(!array_key_exists($key, $jbans)) {
        					$jbans[$key]=$row;
        					$jbans[$key]['job']=array($row['job']);
        					$jbans[$key]['id']=array($row['id']);
        				} else {
        					$jbans[$key]['job'][]=$row['job'];
        					$jbans[$key]['id'][]=$row['id'];
        				}
        				break;
        			case 'PERMABAN':
        			case 'TEMPBAN':
        			case 'CLUWNE':
        			case 'APPEARANCE':
        				if(!array_key_exists($key, $bans)) {
        					$bans[$key]=$row;
        					$bans[$key]['job']=array($row['job']);
        				} else {
        					$bans[$key][]=$row['job'];
        				}
        				break;
        		}
        	}
        }

        // Input filtering.
        $ip = filter_input(INPUT_GET, 'ip', FILTER_VALIDATE_IP, array('default'=>'', 'flags'=>FILTER_FLAG_IPV4|FILTER_FLAG_IPV6));
        $ckey = filter_input(INPUT_GET, 'ckey', FILTER_SANITIZE_STRING, array('default'=>''));
        $cid = filter_input(INPUT_GET, 'cid', FILTER_SANITIZE_STRING, array('default'=>''));

        $this->setTemplateVar('bans', $bans);
        $this->setTemplateVar('jbans', $jbans);
        $this->setTemplateVar('ip', $ip);
        $this->setTemplateVar('ckey', $ckey);
        $this->setTemplateVar('cid', $cid);
        $this->setTemplateVar('bantypes', $types);
        return $this->displayTemplate('web/bans');
    }

    public function OnHeader() {
        $target = fmtAPIURL('findcid');
        $autocomplete = implode("','", Jobs::$KnownJobs);
        return <<<EOF
		 <script type="text/javascript">
$(document).ready(function(){
	//-------------------------------
	// Minimal
	//-------------------------------
	$('.jobs').tagit({
		fieldName: 'jobs[]',
		availableTags: ['{$autocomplete}']
	});
	$("button#getlast").click(function(){
		$.post("{$target}",
		{
		  ckey:$("#banCKey").val()
		},
		function(data,status){
		  //alert("Returned: "+status);
		  if(status=="success"){
		  	rows=data.split("\\n");
		  	$("#banIP").val(rows[0]);
		  	$("#banCID").val(rows[1]);
		  } else {
		  	alert("Couldn't find that ckey.");
		  }
		});
	});
});
		</script>
EOF;
    }

}

Router::Register('/bans/?', new BanListPage());
