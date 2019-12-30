<?php
namespace VGWS\Auth;

class Admin extends \Atera\DBTable
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
        $res = \Atera\DB::GetRow('SELECT * FROM erro_admin WHERE ckey=?', array($ckey));
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
			$span = new \VGWS\HTML\Elements\Element('span',array('class'=>'clm'.$name));
			$span->addClass('flags');
			$span->addClass($hasFlag?'flagset':'flagunset');
			if($showControls)
			{
					$child = new \VGWS\HTML\Elements\Input('checkbox',"flags[{$this->CKey}][]",$flag,array('title'=>$name));
					if($hasFlag)
							$child.setAttribute('checked','checked');
					$span->addChild($child);
			} else {
					$span->addChild($hasFlag?'&#x2713;':'&#x2717;');
			}
			return $span;
		}
}
