<?php
/**
 * DBTable 1.0
 *
 * Easy-to-use interface between the database and an object.
 *
 * Adapted from a Python class I wrote.
 *
 * @author Rob Nelson <nexis@7chan.org>
 */
namespace VGWS\Database;
// Handle translations between the database and the app.
abstract class DBTranslator
{
    public abstract function toDB($input);
    public abstract function fromDB($input);
}

class DBArrayTranslator extends DBTranslator
{
    public $delimiter = ';';
    public function __construct($delim)
    {
        $this->delimiter = $delim;
    }

    public function toDB($input)
    {
        return implode($this->delimiter, $input);
    }

    public function fromDB($input)
    {
        return explode($this->delimiter, $input);
    }

}

class DBJSONTranslator extends DBTranslator
{

    public function toDB($input)
    {
        return json_encode($input);
    }

    public function fromDB($input)
    {
        return json_decode($input);
    }

}

class DBCustomTranslator extends DBTranslator
{
    private $to;
    private $from;

    public function __construct($to, $from)
    {
        $this->to = $to;
        $this->from = $from;
    }

    public function toDB($input)
    {
        $func = $this->to;
        if($func==null) return $input;
        return $func($input);
    }

    public function fromDB($input)
    {
        $func=$this->from;
        if($func==null) return $input;
        return $func($input);
    }

}

abstract class DBTable
{

    /**
     * SQL column to class field associative array.
     *
     * DB column => class field
     */
    protected $_translation = array();

    /**
     * Functions to wrap around the inserted data (such as INET_ATON)
     *
     * DB column => array(function for reading, function for writing)
     */
    protected $_converters = array();

    /**
     * Set of table columns that are a part of the primary key
     */
    protected $_keys = array();

    /**
     * Database table.
     */
    protected $_name = null;

    /**
     * Get a DBArrayTranslator for converting field values.
     * @param string $delim Delimiter to use.
     * @return DBArrayTranslator
     */
    public static function TYPE_ARRAY($delim)
    {
        return new DBArrayTranslator($delim);
    }

    /**
     * Get a DBJSONTranslator for converting field values.
     * @return DBJSONTranslator
     */
    public static function TYPE_JSON()
    {
        return new DBJSONTranslator();
    }

    public function __construct()
    {
        $this->onInitialize();
    }

    /**
     * Override to initialize the table metadata.
     * @return void
     */
    abstract protected function onInitialize();

    /**
     * Override to define behavior that is to occur after processing a row.
     * @return void
     */
    public function onPostLoad()
    {
        // Override.
    }

    /**
     * Override to define behavior that is to occur before INSERTing, REPLACEing,
     * or UPDATE-ing.
     */
    public function onPreSave()
    {
        // Override.
    }

    protected function dbInitialized()
    {
        return empty($this->_name);
    }

    /**
     * Set the database table's name.
     */
    protected function setTableName($tableName)
    {
        $this->_name = $tableName;
    }

    public function getTableBindings()
    {
        return $this->_translation;
    }

    public function getTableKeys()
    {
        return $this->_keys;
    }

    public function getTableConverters()
    {
        return $this->_keys;
    }

    public function getTableName()
    {
        return $this->_name;
    }

    /**
     * Associate a table column with a class field.
     *
     * @param string $sqlfield Table column
     * @param string $pyfield Class field
     * @param boolean $isKey Whether the table column is a part of the primary key
     * @param DBTranslator|null $translator Translator used to convert values to and from a serialized format in the database.
     */
    protected function setFieldAssoc($sqlfield, $pyfield, $isKey = false, $translator = null)
    {
        $this->_translation[$sqlfield] = $pyfield;
        if ($isKey)
            $this->addKey($sqlfield);
        if ($translator != null)
            $this->_converters[$sqlfield] = $translator;
    }

    /**
     * Define a MySQL function to translate the field to/from the form stored in
     * the database.
     * @param sqlField The table column we're messing with
     * @param readFunction The method used when reading from the table (name
     * only)
     * @param writeFunction The method used when writing to the table (name only)
     */
    protected function setFieldTranslator($sqlField, $readFunction, $writeFunction)
    {
        $this->_converters[$sqlField] = new DBCustomTranslator($writeFunction, $readFunction);
    }

    /**
     * Ignore a table column that won't be used by this DBTable (otherwise, you'll get warnings)
     * @param sqlfield Column to ignore
     */
    protected function ignoreField($sqlfield)
    {
        $this->_translation[$sqlfield] = null;
    }

    /**
     * Add a primary key column.
     * @param sqlfield Column that's a part of the primary key
     */
    protected function addKey($sqlfield)
    {
        $this->_keys[] = $sqlfield;
    }

    /**
     * Set class fields from an associative array corresponding to a table row.
     * @param row Row from ADODB (associative array of columnName => value)
     * @param optional Only set non-key values
     */
    public function loadFromRow($row, $optional = false)
    {
        foreach ($row as $key => $value) {
            if (!array_key_exists($key, $this->_translation) && !is_numeric($key)) {
                Page::Message('warning', "Unknown field in table " . $this->_name . ": {$key}");
            }
        }

        foreach ($this->_translation as $dbname => $attrname) {
            if ($attrname == null)
                continue;
            $setval = false;
            if (!$optional)
                $setval = true;
            else
                $setval = !array_key_exists($dbname, $this->_keys) && $this->$attrname != $row[$dbname];
            if ($setval && array_key_exists($dbname, $row)) {
                #Page::Message('info', "\$this->{$attrname} = \$row['{$dbname}']
                # = {$row[$dbname]}");
                $this->$attrname = $row[$dbname];
                if (isset($this->_converters[$dbname]))
                    $this->$attrname = $this->_converters[$dbname]->fromDB($this->$attrname);
            }
        }
        $this->OnPostLoad();
    }

    public static function FromRow($row) {
        $record = new static();
        $record->loadFromRow($row);
        return $record;
    }

    /**
     * Delete this record from the database.
     */
    public function delete()
    {
        $where = array();
        foreach ($this->_translation as $col => $attr) {
            if ($attr == null)
                continue;
            $val = $this->$attr;
            if (isset($this->_converters[$col]))
                $val = $this->_converters[$col]->toDB($val);
            if (in_array($col, $this->_keys)) {
                $where["`{$col}`=?"] = $val;
            }
        }
        $sql = sprintf('DELETE FROM %s WHERE %s', $this->_name, join(' AND ', array_keys($where)));
        $values = array_values($where);
        return DB::Execute($sql, $values);
    }

    /**
     * Insert this class as a new record.
     * @param lastID Return the ID of the new row?
     */
    public function insert($lastID = false)
    {
        $this->OnPreSave();
        global $db;

        $values = array();
        $colList = array();
        foreach ($this->_translation as $dbname => $attrname) {
            if ($attrname == null) {
                continue;
            }

            $value = $this->$attrname;
            if (isset($this->_converters[$dbname]))
                $value = $this->_converters[$dbname]->toDB($value);
            $values[] = $value;
            $colList[] = "`{$dbname}`";
        }
        $sql = sprintf('INSERT INTO `%s` (%s) VALUES (%s)', $this->_name, implode(', ', $colList), implode(', ', array_fill(0, count($values), '?')));
        //var_dump(array_combine($colList,$values));
        $err = DB::Execute($sql, $values);
        if (!$err) {
            Page::Message('error', $db->ErrorMsg());
        }
        if ($lastID)
            return DB::Insert_ID();
        return false;
    }

    /**
     * Replace the existing record with this record.
     */
    public function replace()
    {
        $this->OnPreSave();
        global $db;

        $values = array();
        $colList = array();
        foreach ($this->_translation as $dbname => $attrname) {
            if ($attrname == null)
                continue;
            $value = $this->attrname;
            if (isset($this->_converters[$dbname]))
                $value = $this->_converters[$dbname]->toDB($value);
            $values[] = $value;
            $colList[] = "`{$dbname}`";
        }
        $sql = sprintf('REPLACE INTO `%s` (%s) VALUES (%s)', $this->_name, implode(', ', $colList), implode(', ', array_fill(0, count($values), '?')));
        $err = $db->Execute($sql, $values);
        if (!$err) {
            Page::Message('error', $db->ErrorMsg());
        }
    }

    /**
     * Update the corresponding database entry with the data in this class.
     */
    public function update()
    {
        $this->OnPreSave();
        global $db;
        $fields = array();
        $where = array();
        foreach ($this->_translation as $col => $attr) {
            if ($attr == null)
                continue;
            $val = $this->$attr;
            if (isset($this->_converters[$col]))
                $val = $this->_converters[$col]->toDB($val);
            if (in_array($col, $this->_keys)) {
                $where["`{$col}`=?"] = $val;
            } else {
                $qmarks[] = '?';
                $fields["`{$col}`=?"] = $val;
            }
        }

        $sql = sprintf('UPDATE %s SET %s WHERE %s', $this->_name, implode(', ', array_keys($fields)), join(' AND ', array_keys($where)));
        $values = array_values($fields);
        foreach (array_values($where) as $val)
            $values[] = $val;

        return DB::Execute($sql, $values);
    }

}
