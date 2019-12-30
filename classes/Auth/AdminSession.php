<?php
namespace \VGWS\Auth;
use \Atera\DB;
class AdminSession
{
    public $ckey = '';
    public $rank = '';
    public $level = 0;
    public $flags = 0;
    public $id = '';

    public static function FetchSessionFor($sessID)
    {
        global $db;
        $query = <<<SQL
    SELECT
        a.ckey,
        a.rank,
        a.level,
        a.flags,
        s.sessID
    FROM
        erro_admin AS a
    LEFT JOIN
        admin_sessions AS s
    ON a.ckey = s.ckey
    WHERE s.sessID=?
SQL;
        $row = DB::GetRow($query, array($sessID));
        if (count($row) == 0)
            return false;
        $sess = new AdminSession();
        $sess->id = $row[4];
        $sess->ckey = $row[0];
        $sess->role = $row[1];
        $sess->rank = $row[2];
        $sess->flags = $row[3];
        return $sess;
    }

}
