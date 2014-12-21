<?php

/**
 * All valid identifier escaping characters *that we want to fix*, as a string.
 */
define('ALL_IDENT_DELIMITERS', '`');

class DB_Compatibility
{
    /**
     * HIGH_PRIORITY replacement.
     */
    public $HighPriority = '';

    /**
     * Identifier escaping character to transform to.
     *
     *  e.g. SELECT * FROM `from`
     *
     * Double-quotes are ANSI, so default.
     */
    public $IdentEscapeChar = '"';

    /**
     * Characters that indicate the beginning of a string.
     */
    public $StringDelimiters = '\'';

    private $fixTrue = false;
    public $BooleanTrue = 'TRUE';

    private $fixFalse = false;
    public $BooleanFalse = 'FALSE';

    /*
     * States
     */
    const ST_INITIAL = 0;
    const ST_IN_STRING = 1;
    const ST_IN_IDENTIFIER = 2;

    // "Feature" flags and whatnot.
    private $fixHighPriority = false;

    /**
     * Sanity checks and stuff.
     */
    public function __construct()
    {
        // Only fix HIGH_PRIORITY if we need to.
        $this->fixHighPriority = 'HIGH_PRIORITY' != $this->HighPriority;
        $this->fixTrue = 'TRUE' != $this->BooleanTrue;
        $this->fixFalse = 'FALSE' != $this->BooleanFalse;
    }

    /**
     * Fix escape characters, invalid keywords.
     * @param $params array SQL statement to repair.
     * @return Fixed SQL params.
     */
    public function FixParams($params)
    {
        return $params;
    }

    /**
     * Build a LIMIT statement.
     * @param $limit int The number of records to return.
     * @param $offset int Offset from the beginning record.
     * @return Fixed SQL params.
     */
    public function BuildLimit($limit, $offset = 0)
    {
        // Turns out, this works okay with MySQL AND postgres.
        $o = 'LIMIT ' . $limit;
        if ($offset != 0) {
            $o = ' OFFSET ' . $offset;
        }
        return $o;
    }

    /**
     * Fix escape characters, invalid keywords.
     * @param $sql string SQL statement to repair.
     * @return Fixed SQL statement.
     */
    public function FixQuery($sql)
    {
        // Shitty state machine.
        $cstate = self::ST_INITIAL;

        // honk
        $newsql = '';

        // Checking for escape characters.
        $char_escaped = false;

        // Replace HIGH_PRIORITY, if need be.
        if ($this->fixHighPriority)
            $sql = str_replace('HIGH_PRIORITY', $this->HighPriority, $sql);

        // Iterate through each character in the query.
        for ($i = 0; $i < strlen($sql); $i++) {
            $c = $sql[$i];

            if (!$char_escaped) {
                // @formatter:off
                // Are we an identifier escape character?
                // Are we not inside of a string?
                if(strpos(ALL_IDENT_DELIMITERS,$c)!==false && $cstate!=self::ST_IN_STRING) {
                    // Fix character.
                    $newsql.=$this->IdentEscapeChar;
                    
                    // Set new state.
                    $cstate=($cstate==self::ST_INITIAL) ? self::ST_IN_IDENTIFIER : self::ST_INITIAL;
                    continue;
                }
                
                // Are we a string delimiter?
                // Are we not inside of a string?
                elseif(strpos($this->StringDelimiters,$c)!==false) {
                    // Set new state.
                    $cstate=($cstate==self::ST_INITIAL) ? self::ST_IN_STRING : self::ST_INITIAL;
                }
                
                elseif($cstate!=self::ST_IN_STRING)
                {
                    // Check for {P} (prefix char)
                    /*
                    if(substr($sql,$i,3)=='{P}') {
                        $i+=2;
                        $newsql.=KU_DBPREFIX;
                        continue;
                    }
                    */
                    
                    // Check for TRUE
                    // elseif
                    if(substr($sql,$i,3)=='TRUE') {
                        $i+=3;
                        $newsql.=$this->BooleanTrue;
                        continue;
                    }
                    
                    // Check for FALSE
                    elseif(substr($sql,$i,3)=='FALSE') {
                        $i+=4;
                        $newsql.=$this->BooleanFalse;
                        continue;
                    }
                }
                
                // Escaped
                elseif($c=='\\') {
                    $char_escaped=true;
                    $newsql.=$c;
                    continue;
                }
                // @formatter:on
            }

            // Turn off escaping for the next character.
            $char_escaped = false;

            // Throw the new character on the buffer.
            $newsql .= $c;
        }

        return $newsql;
    }

}

/**
 * Compatibility layer for Postgres.
 */
class DB_PostgresCompatibility extends DB_Compatibility
{
    // THAT'S RIGHT KIDS! POSTGRES IS TOO STUPID TO HANDLE BOOLEANS PROPERLY!
    public function FixParams($args)
    {
        //http://php.net/manual/en/function.pg-query-params.php#115063
        //https://bugs.php.net/bug.php?id=68156 (by yours truly)
        $output = array();
        foreach ($args as &$value) {
            if (is_bool($value)) {
                $value = ($value) ? $this->BooleanTrue : $this->BooleanFalse;
            }
        }
        return $args;
    }

}

/**
 * Compatibility layer for MySQL/MariaDB.
 */
class DB_MySqlCompatibility extends DB_Compatibility
{
    public $HighPriority = 'HIGH_PRIORITY';
    public $IdentEscapeChar = '`';
}

class DB
{
    /**
     * ADODB connection we're wrapping up.
     */
    private static $conn;
    private static $compat;

    //@formatter:off
    private static $CompatibilityTypes = array(
        'mysql'      => 'DB_MySqlCompatibility', 
        'mysqli'     => 'DB_MySqlCompatibility',
         
        'postgres64' => 'DB_PostgresCompatibility', 
        'postgres7'  => 'DB_PostgresCompatibility', 
        'postgres8'  => 'DB_PostgresCompatibility', 
        'postgres9'  => 'DB_PostgresCompatibility'
    );
    //@formatter:on

    public static function Initialize()
    {
        global $kx_db;

        // Start this bitch up.
        static::$conn = NewADOConnection(DB_DSN);
        static::$compat = new static::$CompatibilityTypes[DB_TYPE]();

        // Error reporting.
        #static::$conn->raiseErrorFn = 'ku_adodb_error';

        // SQL debug
        if (false) {
            static::$conn->debug = true;
        }
        static::$conn->Execute("SET NAMES 'utf8'");
        $kx_db = new DBProxy;
    }
    
    public static function Debug($on) {
        static::$conn->debug=$on;
    }

    public static function InitForTesting()
    {
        static::$compat = new DB_PostgresCompatibility();
    }

    public static function QuoteColumn($columnName)
    {
        $columnName = trim($columnName, self::$compat->IdentEscapeChar . ALL_IDENT_DELIMITERS);
        return self::$compat->IdentEscapeChar . $columnName . self::$compat->IdentEscapeChar;
    }

    public static function QuoteTable($columnName)
    {
        $columnName = trim($columnName, self::$compat->IdentEscapeChar . ALL_IDENT_DELIMITERS);
        return self::$compat->IdentEscapeChar . $columnName . self::$compat->IdentEscapeChar;
    }

    public static function FixSQL($sql)
    {
        //$sql.=str_replace('{P}',KU_DBPREFIX,$sql);
        return static::$compat->FixQuery($sql);
    }

    public static function FixArgs($args)
    {
        return static::$compat->FixParams($args);
    }

    public static function ErrorMsg()
    {
        return static::$conn->ErrorMsg();
    }

    public static function Execute($sql, array $args = array())
    {
        $sql = static::FixSQL($sql);
        $args = static::FixArgs($args);
        return static::$conn->Execute($sql, $args);
    }

    public static function CacheExecute($sql, array $args = array())
    {
        $sql = static::FixSQL($sql);
        $args = static::FixArgs($args);
        return static::$conn->CacheExecute($sql, $args);
    }

    public static function GetAll($sql, array $args = array())
    {
        $sql = static::FixSQL($sql);
        $args = static::FixArgs($args);
        return static::$conn->GetAll($sql, $args);
    }

    public static function GetOne($sql, array $args = array())
    {
        $sql = static::FixSQL($sql);
        $args = static::FixArgs($args);
        return static::$conn->GetOne($sql, $args);
    }

    public static function GetRow($sql, array $args = array())
    {
        $sql = static::FixSQL($sql);
        $args = static::FixArgs($args);
        return static::$conn->GetRow($sql, $args);
    }

    public static function Insert_Id()
    {
        return static::$conn->Insert_Id();
    }

    public static function BuildPagedLimit(int $desiredPage, int $itemsPerPage)
    {
        $offset = ($desiredPage - 1) * $itemsPerPage;
        return static::$compat->BuildLimit($itemsPerPage, $offset);
    }

    public static function BuildTuple(array $items, $asString = false)
    {
        if ($asString) {
            $quote = function($i)
            {
                return DB::$conn->qstr($i);
            };
            $items = array_map($quote, $items);
        }
        return '(' . implode(',', $items) . ')';
    }

}

class DBProxy
{
    static $SUPPORTED_FUNCTIONS = array('Execute', 'GetAll', 'GetOne', 'ErrorMsg');
    static $ALIASES = array('getOne' => 'GetOne');
    public function __call($name, array $args = array())
    {
        // TODO: Add bitching here.
        if (array_key_exists($name, self::$ALIASES)) {
            $name = self::$ALIASES[$name];
        }
        if (!in_array($name, self::$SUPPORTED_FUNCTIONS)) {
            print('WARNING:  Unknown ADODB function ' . $name);
        }
        return DB::$name(...$args); // This line fucks up everything that isn't ready for PHP 5.6.
                                    // Comment it out if you're using a linter or doing code analysis.
    }

}

/**
 * Alias for DB::QuoteColumn (for use with array_map)
 */
function quoteColumn($columnName)
{
    return DB::QuoteColumn($columnName);
}

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
        $op = new CompoundInsert($dbtables[0]);
        foreach ($dbtables as $dbtable) {
            $op->AddRecord($dbtable);
        }
        return $op;
    }

    public function AddRecord(DBTable $dbtable)
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
        $sql = "INSERT INTO " . DB::QuoteTable($this->TableName) . " (" . implode(',', $this->Fields) . ") VALUES " . implode(',', $blocks);
        if ($returnSQL)
            return $sql;
        $err = DB::Execute($sql, $values);
        if (!$err) {
            Page::Message('error', DB::ErrorMsg());
            return false;
        }
        return true;
    }

}
