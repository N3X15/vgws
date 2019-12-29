<?php
namespace VGWS\Auth;

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

		public function getRenderedFlags(int $flag, string $name, bool $showControls){
			$hasFlag=($this->Flags & $flag) == $flag;
			$span = new Element('span',array('class'=>'clm'.$name));
			$span->addClass('flags');
			$span->addClass($hasFlag?'flagset':'flagunset');
			if($showControls)
			{
					$child = new Input('checkbox',"flags[{$this->CKey}][]",$flag,array('title'=>$name));
					if($hasFlag)
							$child.setAttribute('checked','checked');
					$span->addChild($child);
			} else {
					$span->addChild($hasFlag?'&#x2713;':'&#x2717;');
			}
			return $span;
		}
}
