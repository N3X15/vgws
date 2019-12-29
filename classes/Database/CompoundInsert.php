<?php
namespace VGWS\Database;

class CompoundInsert
{
    public $Fields = array();
    public $Records = array();
    public $TableName = '';

    public function __construct(DBTable $dbtable)
    {
        $this->Fields = array_map('quoteColumn', array_keys($dbtable->getTableBindings()));
        $this->TableName = $dbtable->getTableName();
    }

    public static function Build(array $dbtables)
    {
        $op = new \VGWS\Database\CompoundInsert($dbtables[0]);
        foreach ($dbtables as $dbtable) {
            $op->AddRecord($dbtable);
        }
        return $op;
    }

    public function AddRecord(\VGWS\Database\DBTable $dbtable)
    {
        $values = array();
        $converters = $dbtable->getTableConverters();
        foreach ($dbtable->getTableBindings() as $dbname => $attrname) {
            if ($attrname == null)
                continue;
            $value = $dbtable->$attrname;
            if (isset($converters[$dbname]))
                $value = $converters[$dbname]->toDB($value);
            $values[] = $value;
        }
        $this->Records[] = $values;
    }

    public function Execute($returnSQL = false)
    {
        $values = array();
        $colList = array();
        $blocks = array();
        foreach ($this->Records as $record) {
            foreach ($record as $dbname => $value) {
                $values[] = $value;
            }
            $blocks[] = '(' . implode(',', array_fill(0, count($record), '?')) . ')';
        }
        $sql = "INSERT INTO " . \VGWS\Database\DB::QuoteTable($this->TableName) . " (" . implode(',', $this->Fields) . ") VALUES " . implode(',', $blocks);
        if ($returnSQL)
            return $sql;
        $err = \VGWS\Database\DB::Execute($sql, $values);
        if (!$err) {
            \VGWS\Content\Page::Message('error', \VGWS\Database\DB::ErrorMsg());
            return false;
        }
        return true;
    }

}
