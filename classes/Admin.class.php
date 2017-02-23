<?php

define('R_BUILDMODE', 1);
define('R_ADMIN', 2);
define('R_BAN', 4);
define('R_FUN', 8);
define('R_SERVER', 16);
define('R_DEBUG', 32);
define('R_POSSESS', 64);
define('R_PERMISSIONS', 128);
define('R_STEALTH', 256);
define('R_REJUVINATE', 512);
define('R_VAREDIT', 1024);
define('R_SOUNDS', 2048);
define('R_SPAWN', 4096);
define('R_MOD', 8192);
define('R_ADMINBUS', 16384);
define('R_POLLING', 32768);

define('R_MAXPERMISSION', 32768); //This holds the maximum value for a permission. It is used in iteration, so keep it updated.
define('R_HOST', 65535);
define('R_EVERYTHING', R_BUILDMODE | R_ADMIN | R_BAN | R_FUN | R_SERVER | R_DEBUG | R_POSSESS | R_PERMISSIONS | R_STEALTH | R_REJUVINATE | R_VAREDIT | R_SOUNDS | R_SPAWN | R_MOD | R_ADMINBUS | R_POLLING);
define('R_GAME_ADMIN', R_ADMIN | R_SPAWN | R_REJUVINATE | R_VAREDIT | R_BAN | R_POSSESS | R_FUN | R_SOUNDS | R_SERVER | R_DEBUG | R_STEALTH | R_BUILDMODE | R_MOD | R_ADMINBUS);

// @formatter:off
$ADMIN_FLAGS=array(
	R_BUILDMODE   => 'BUILDMODE',
  R_ADMIN       => 'ADMIN',
  R_MOD         => 'MOD',
	R_BAN         => 'BAN',
	R_FUN         => 'FUN',
	R_SERVER      => 'SERVER',
	R_DEBUG       => 'DEBUG',
	R_POSSESS     => 'POSSESS',
	R_PERMISSIONS => 'PERMISSIONS',
	R_STEALTH     => 'STEALTH',
	R_REJUVINATE  => 'REJUVINATE',
	R_VAREDIT     => 'VAREDIT',
	R_SOUNDS      => 'SOUNDS',
	R_SPAWN       => 'SPAWN',
	R_ADMINBUS    => 'ADMINBUS',
	R_POLLING     => 'POLLS',
);

$ADMIN_RANKS=[
  'Host'       => R_EVERYTHING,
  'Game Admin' => R_GAME_ADMIN,
  'ZERO' => 0,
];
// @formatter:on

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

class Admin extends DBTable
{
    public $ID = 0;
    public $CKey = '';
    public $Rank = '';
    public $Level = 0;
    public $Flags = 0;

    protected function onInitialize()
    {
        $this->setTableName('erro_admin');

        $this->setFieldAssoc('id', 'ID', true);

        $this->setFieldAssoc('ckey', 'CKey');
        $this->setFieldAssoc('rank', 'Rank');
        $this->setFieldAssoc('level', 'Level');
        $this->setFieldAssoc('flags', 'Flags');

        $this->setFieldTranslator('level', 'intval', null);
        $this->setFieldTranslator('flags', 'intval', null);
    }

    public static function FindCKey($ckey)
    {
        $res = DB::GetRow('SELECT * FROM erro_admin WHERE ckey=?', array($ckey));
        if ($res == null)
            return null;
        return self::FromRow($res);
    }

    public function hasRight($rightflag) {
        return ($this->Flags & $rightflag) == $rightflag;
    }

    public function canEdit(Admin $admin) {
        return $this->hasRight(R_PERMISSIONS) && $this->Level > $admin->Level;
    }
}
