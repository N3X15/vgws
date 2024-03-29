<?php
/**
 *
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `polltype` varchar(16) NOT NULL DEFAULT 'OPTION',
 `starttime` datetime NOT NULL,
 `endtime` datetime NOT NULL,
 `question` varchar(255) NOT NULL,
 `adminonly` tinyint(1) DEFAULT '0',
 */
namespace VGWS\Polls;
use Atera\DB;
use Atera\DBTable;
class Poll {
    // erro_poll_question
    public $ID = -1;
    public $question = '';
    public $type = '';
    public $start = 0;
    public $end = 0;

    // erro_poll_options
    public $options = array();

    public function __construct($row = null) {
        if (is_array($row)) {
            $this->ID = intval($row['id']);
            $this->question = $row['question'];
            $this->type = $row['polltype'];
            $this->start = $row['starttime'];
            $this->end = $row['endtime'];
            $this->adminonly = $row['adminonly'];
        }
    }

    public function Save() {
        global $db;
        $row = array();
        if ($this->ID > -1)
            $row['id'] = $this->ID;
        $row['question'] = $this->question;
        $row['polltype'] = $this->type;
        $row['starttime'] = $this->start;
        $row['endtime'] = $this->end;
        $row['adminonly'] = intval($this->adminonly);
        if ($this->ID == -1)
            doInsertSQL('erro_poll_question', $row);
        else
            doUpdateSQL('erro_poll_question', $row, 'id=' . intval($this->ID));
    }

    public static function GetByID($id) {
        $res = DB::Execute('SELECT * FROM erro_poll_question WHERE id=?', array($id));
        if (!$res)
            return null;
        foreach ($res as $row) {
            return new Poll($row);
        }
        return null;
    }

    public function GetVotes() {
        /*
         `id` int(11) NOT NULL AUTO_INCREMENT,
         `datetime` datetime NOT NULL,
         `pollid` int(11) NOT NULL,
         `optionid` int(11) NOT NULL,
         `ckey` varchar(255) NOT NULL,
         `ip` varchar(16) NOT NULL,
         `adminrank` varchar(32) NOT NULL,
         `rating` int(2) DEFAULT NULL,
         */
        switch($this->type) {
            //Polls that have enumerated options
            case "OPTION" :
            case "MULTICHOICE" :
                // I think
                return $this->GetVotesForOption();
            case "NUMVAL" :
                return $this->GetVotesForNumVal();
            case "TEXT" :
                return $this->GetVotesForText();
        }
        return null;
    }

    public function LoadOptions() {
        global $db;
        $res = DB::Execute('SELECT * FROM erro_poll_option WHERE pollid=?', array($this->ID));
        foreach ($res as $row) {
            $opt = PollOption::FromRow($row);
            $this->options[$opt->ID] = $opt;
        }
    }

    public function GetVotesForOption() {
        global $db;
        $res = DB::Execute('SELECT COUNT(*) as count, optionid FROM erro_poll_vote WHERE pollid=? GROUP BY optionid ORDER BY COUNT(*) DESC', array($this->ID));
        if (!$res)
            return null;
        $results = array();
        $results['total'] = 0;
        $results['winner'] = 0;
        foreach ($res as $row) {
            $optID = intval($row['optionid']);
            $optCount = intval($row['count']);
            if ($optCount > $results['winner'])
                $results['winner'] = $optCount;
            $results[$optID] = $optCount;
            $results['total'] += $optCount;
        }
        return $results;
    }

    public function GetVotesForNumVal() {
        global $db;
        $res = DB::Execute('SELECT COUNT(*) as count, optionid, rating FROM erro_poll_vote WHERE pollid=? GROUP BY optionid, rating ORDER BY COUNT(*) DESC', array($this->ID));
        if (!$res)
            return null;
        $results = array();
        foreach ($res as $row) {
            $optID = intval($row['optionid']);
            $opt = $this->options[$optID];
            $optCount = intval($row['count']);
            $rating = intval($row['rating']);
            if ($opt->maxVal >= $rating && $opt->minVal <= $rating) {
                if (!array_key_exists($optID, $results)) {
                    $results[$optID] = array('total' => 0, 'winner' => 0);
                }
                if ($optCount > $results[$optID]['winner'])
                    $results[$optID]['winner'] = $optCount;
                $results[$optID][$rating] = $optCount;
                $results[$optID]['total'] += $optCount;
            }
        }
        return $results;
    }

    public function GetVotesForText() {
        global $db;
        /*
         `id` int(11) NOT NULL AUTO_INCREMENT,
         `datetime` datetime NOT NULL,
         `pollid` int(11) NOT NULL,
         `ckey` varchar(32) NOT NULL,
         `ip` varchar(18) NOT NULL,
         `replytext` text NOT NULL,
         `adminrank` varchar(32) NOT NULL DEFAULT 'Player',
         */
        $res = DB::Execute('SELECT replytext,ckey FROM erro_poll_textreply WHERE pollid=?', array($this->ID));
        if (!$res)
            return null;
        $results = array();
        foreach ($res as $row) {
            $results[$row['ckey']] = $row['replytext'];
        }
        return $results;
    }

}
