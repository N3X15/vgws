<?php
/**
 * DBTable 1.0
 *
 * Easy-to-use interface between the database and an object.
 *
 * Adapted from a Python class I wrote.
 *
 * @author Rob Nelson <rob@squad.com.mx>
 */

class DBTable {

    /**
     * SQL column to class field associative array.
     *
     * DB column => class field
     */
    private $_translation = array();

    /**
     * Functions to wrap around the inserted data (such as INET_ATON)
     *
     * DB column => array(function for reading, function for writing)
     */
    private $_converters = array();

    /**
     * Set of table columns that are a part of the primary key
     */
    private $_keys = array();

    /**
     * Database table.
     */
    private $_name = '';

    /**
     * Override to define behavior that is to occur after processing a row.
     */
    public function OnPostLoad() {
        // Override.
    }

    /**
     * Override to define behavior that is to occur before INSERTing, REPLACEing,
     * or UPDATE-ing.
     */
    public function OnPreSave() {
        // Override.
    }

    /**
     * Set the database table's name.
     */
    public function setTableName($tableName) {
        $this->_name = $tableName;
    }

    /**
     * Associate a table column with a class field.
     *
     * @param sqlfield Table column
     * @param pyfield Class field
     * @param isKey Whether the table column is a part of the primary key
     */
    public function setFieldAssoc($sqlfield, $pyfield, $isKey = false) {
        $this->_translation[$sqlfield] = $pyfield;
        if ($isKey)
            $this->addKey($sqlfield);
    }

    /**
     * Define a MySQL function to translate the field to/from the form stored in
     * the database.
     * @param sqlField The table column we're messing with
     * @param readFunction The method used when reading from the table (name
     * only)
     * @param writeFunction The method used when writing to the table (name only)
     */
    public function setFieldTranslator($sqlField, $readFunction, $writeFunction) {
        $this->_converters[$sqlField] = array($readFunction, $writeFunction);
    }

    /**
     * Ignore a table column (otherwise, you'll get warnings)
     * @param sqlfield Column to ignore
     */
    public function ignoreField($sqlfield) {
        $this->_translation[$sqlfield] = null;
    }

    /**
     * Add a primary key column.
     * @param sqlfield Column that's a part of the primary key
     */
    public function addKey($sqlfield) {
        $this->_keys[] = $sqlfield;
    }

    /**
     * Set class fields from an associative array corresponding to a table row.
     * @param row Row from ADODB (associative array of columnName => value)
     * @param optional Only set non-key values
     */
    public function LoadFromRow($row, $optional = false) {
        foreach ($row as $key => $value) {
            if (!array_key_exists($key, $this->_translation) && !is_numeric($key)) {
                Page::Message('warning', "Unknown field in table {$this->_name}: {$key}");
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
            }
        }
        $this->OnPostLoad();
    }

    /**
     * Delete this record from the database.
     */
    public function Delete() {
        global $db;
        $where = array();
        foreach ($this->_translation as $col => $attr) {
            if ($attr == null)
                continue;
            $val = $this->$attr;
            if (in_array($col, $this->_keys)) {
                $where["`{$col}`=?"] = $val;
            }
        }
        $sql = sprintf('DELETE FROM %s WHERE %s', $this->_name, join(' AND ', array_keys($where)));
        $values = array_values($where);
        return $db->Execute($sql, $values);
    }

    /**
     * Insert this class as a new record.
     * @param lastID Return the ID of the new row?
     */
    public function Insert($lastID = false) {
        $this->OnPreSave();
        global $db;

        $values = array();
        $qmarks = array();
        $colList = array();
        foreach ($this->_translation as $dbname => $attrname) {
            if ($attrname == null)
                continue;
            $values[] = $this->$attrname;
            $q = '?';
            if (isset($this->_converters[$dbname]))
                $q = "{$this->_converters[$dbname][1]}(?)";
            $qmarks[] = $q;
            $colList[] = "`{$dbname}`";
        }
        $sql = "INSERT INTO `{$this->_name}` (" . implode(', ', $colList) . ") VALUES (" . implode(', ', $qmarks) . ")";
        $err = $db->Execute($sql, $values);
        if (!$err) {
            Page::Message('error', $db->ErrorMsg());
        }
        if ($lastID)
            return $db->Insert_ID();
        return false;
    }

    /**
     * Replace the existing record with this record.
     */
    public function Replace() {
        $this->OnPreSave();
        global $db;

        $values = array();
        $qmarks = array();
        $colList = array();
        foreach ($this->_translation as $dbname => $attrname) {
            if ($attrname == null)
                continue;
            $values[] = $this->$attrname;
            $q = '?';
            if (isset($this->_converters[$dbname]))
                $q = "{$this->_converters[$dbname][1]}(?)";
            $qmarks[] = $q;
            $colList[] = "`{$dbname}`";
        }
        $sql = "REPLACE INTO `{$this->_name}` (" . implode(', ', $colList) . ") VALUES (" . implode(', ', $qmarks) . ")";
        $err = $db->Execute($sql, $values);
        if (!$err) {
            Page::Message('error', $db->ErrorMsg());
        }
    }

    /**
     * Update the corresponding database entry with the data in this class.
     */
    public function Update() {
        $this->OnPreSave();
        global $db;
        $fields = array();
        $where = array();
        foreach ($this->_translation as $col => $attr) {
            if ($attr == null)
                continue;
            $val = $this->$attr;
            if (in_array($col, $this->_keys)) {
                $where["`{$col}`=?"] = $val;
            } else {
                $q = '?';
                if (isset($this->_converters[$col]))
                    $q = "{$this->_converters[$col][1]}(?)";
                $qmarks[] = $q;
                $fields["`{$col}`={$q}"] = $val;
            }
        }
        $sql = sprintf('UPDATE %s SET %s WHERE %s', $this->_name, implode(', ', array_keys($fields)), join(' AND ', array_keys($where)));
        $values = array_values($fields);
        foreach (array_values($where) as $val)
            $values[] = $val;
        return $db->Execute($sql, $values);
    }

}
