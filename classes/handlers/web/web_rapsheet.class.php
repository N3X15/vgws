<?php

class rapsheet_handler extends Page {
	public $parent='';
	public $title = "Rapsheet";
	public $image = "/img/admins.png";
	public function OnBody() {
		global $ADMIN_FLAGS;
		$types=array(
			'PERMABAN',
			'TEMPBAN',
			'JOB_PERMABAN',
			'JOB_TEMPBAN',
			'APPEARANCE'
		);
		
		//$db->debug=true;
		$res = DB::Execute("SELECT * FROM erro_ban
		WHERE
			ckey=?
		ORDER BY ckey",array($_REQUEST['ckey']));
		if(!$res)
			SQLError($db->ErrorMsg());
        $bans = array();
        foreach($res as $row) {
            $row['ban_active']=false;
            switch($row['bantype']) {
                case 'PERMABAN':
                case 'APPEARANCE':
                case 'JOB_PERMABAN':
                    $row['ban_active']=true;
                    break;
                case 'TEMPBAN':
                case 'CLUWNE':
                case 'JOB_TEMPBAN':
                    $row['ban_active'] = $row['expiration_time'] > time();
                    break;
            }
            if(is_null($row['ban_active']))
                $row['ban_active']=false;
        }
		$this->setTemplateVar('bans',$res);
		
		$pd = DB::Execute("SELECT * FROM erro_player
		WHERE
			ckey=?
		ORDER BY ckey",array($_REQUEST['ckey']));
		if(!$pd)
			SQLError($db->ErrorMsg());
		$this->setTemplateVar('playerdata', $pd);
		
		$this->setTemplateVar('bantypes',$types);
		$this->setTemplateVar('ckey',$_REQUEST['ckey']);
		return $this->displayTemplate('web/rapsheet.tpl.php');
	}
	public function OnHeader() {
		$target = fmtAPIURL('findcid');
		$autocomplete=implode("','",Jobs::$KnownJobs);
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
		
Page::Register('web_rapsheet',new rapsheet_handler);