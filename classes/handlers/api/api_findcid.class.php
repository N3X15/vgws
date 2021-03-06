<?php
use \VGWS\Content\Page;
use \Atera\DB;
class findcid_handler extends Page {
	public $parent = '';
	public $description = "Find IP and CID of a given CKey";

	public function OnBody() {
		global $tpl, $db;
		//$db->debug=true;

		$ckey=$_REQUEST['ckey'];

		$res = DB::Execute("
		SELECT
			ip,
			computerid
		FROM
			erro_player
		WHERE
			ckey = ?",
			array($ckey));
		if(!$res)
		{
			header("HTTP/1.1 500 Internal Server Error");
			die('ERROR: '.$db->ErrorMsg());
		}

		foreach($res as $row) {
			$ip = $row[0];
			$pcid = $row[1];
			header("HTTP/1.1 200 OK");
			echo "{$ip}\n{$pcid}";
			exit();
		}
		header("HTTP/1.1 400 Not Found");
		echo "<h1>Not found</h1>";
		exit();
	}
}
Page::Register('api_findcid', new findcid_handler);
