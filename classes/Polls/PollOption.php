<?php
namespace VGWS\Polls;
use Atera\DB;
use Atera\DBTable;
/*
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `pollid` int(11) NOT NULL,
 `text` varchar(255) NOT NULL,
 `percentagecalc` tinyint(1) NOT NULL DEFAULT '1',
 `minval` int(3) DEFAULT NULL,
 `maxval` int(3) DEFAULT NULL,
 `descmin` varchar(32) DEFAULT NULL,
 `descmid` varchar(32) DEFAULT NULL,
 `descmax` varchar(32) DEFAULT NULL,
 */
class PollOption extends DBTable {
    // erro_poll_options
    public $ID = 0;
    public $pollID = 0;
    public $text = '';
    public $calculated = 1;

    public $minVal = 0;
    public $maxVal = 0;

    public $descMin = '';
    public $descMid = '';
    public $descMax = '';

    protected function onInitialize() {
        $this->setTableName('erro_poll_option');

        $this->setFieldAssoc('id', 'ID', true);
        $this->setFieldAssoc('pollid', 'pollID', true);
        $this->setFieldAssoc('text', 'text');
        //$this->setFieldAssoc('calculated', 'percentagecalc');

        $this->setFieldAssoc('minval', 'minVal');
        $this->setFieldAssoc('maxval', 'maxVal');

        $this->setFieldAssoc('descmin', 'descMin');
        $this->setFieldAssoc('descmid', 'descMid');
        $this->setFieldAssoc('descmax', 'descMax');
    }

    public static function GetByID($pollID,$id) {
        global $db;
        $res = DB::Execute('SELECT * FROM erro_poll_option WHERE pollid=? AND id=?', array($pollID,$id));
        if (!$res)
            SQLError("Failed to get poll option " . intval($id).' from poll '.intval($pollID));
        foreach ($res as $row) {
            return PollOption::FromRow($row);
        }
        return null;
    }

	public function Delete() {
		parent::Delete();
		DB::Execute('DELETE FROM erro_poll_vote WHERE optionid=?', [$this->ID]);
	}

}
