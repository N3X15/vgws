<?php

class bans_handler extends Page {
    public $parent = '/';
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
        $this->setTemplateVar('bans', $res);
        $this->setTemplateVar('bantypes', $types);
        return $this->displayTemplate('web/bans.tpl.php');
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

Page::Register('web_bans', new bans_handler);