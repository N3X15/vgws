<?php
namespace VGWS\Database;


class DB
{
    /**
     * ADODB connection we're wrapping up.
     */
    public static $conn;
    private static $compat;

    //@formatter:off
    private static $CompatibilityTypes = array(
        'mysql'      => '\\VGWS\\Database\\Compatibility\\MySQLCompatibility',
        'mysqli'     => '\\VGWS\\Database\\Compatibility\\MySQLCompatibility',

        'postgres64' => '\\VGWS\\Database\\Compatibility\\PostgresCompatibility',
        'postgres7'  => '\\VGWS\\Database\\Compatibility\\PostgresCompatibility',
        'postgres8'  => '\\VGWS\\Database\\Compatibility\\PostgresCompatibility',
        'postgres9'  => '\\VGWS\\Database\\Compatibility\\PostgresCompatibility'
    );
    //@formatter:on
    // Not used in VGWS
    public static $AllTables=[
        #'phinxlog',
    ];

    public static function Initialize()
    {
        global $db;

        // Start this bitch up.
        static::$conn = NewADOConnection(DB_DRIVER);
        static::$compat = new static::$CompatibilityTypes[DB_DRIVER]();

        // Error reporting.
        #static::$conn->raiseErrorFn = 'ku_adodb_error';

        $concheck=null;
        if (DB_PERSISTENT) {
            $concheck=static::$conn->PConnect(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_SCHEMA) or die('SQL database connection error: ' . static::$conn->ErrorMsg());
        } else {
            $concheck=static::$conn->Connect(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_SCHEMA) or die('SQL database connection error: ' . static::$conn->ErrorMsg());
        }
        if(!$concheck) {
            die('Database failed to connect: '.DB::ErrorMsg());
        }
        //  $this->conn->SetFetchMode(ADODB_FETCH_ASSOC);

        // SQL debug
        if (DB_DEBUG) {
            static::$conn->debug = true;
        }
        static::$conn->Execute("SET NAMES 'utf8mb4'");
        static::$conn->Execute("SET time_zone = '+00:00'");
        $db = new DBProxy;
    }

    public static function Debug($on) {
        static::$conn->debug = $on;
    }

    public static function InitForTesting()
    {
        static::$compat = new \VGWS\Database\Compatibility\PostgresCompatibility();
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

    public static function ErrorNo()
    {
        return static::$conn->ErrorNo();
    }

    public static function AffectedRows()
    {
        return static::$conn->Affected_Rows();
    }

    public static function Insert_ID() {
        return static::$conn->Insert_ID();
    }

    public static function GetVariable($varID)
    {
        return static::$conn->GetOne("SELECT @@SESSION.".$varID);
    }

    public static function SetVariable($varID,$value,$verbose=false)
    {
        $cval=static::GetVariable($varID);
        if(is_float($value))
            $cval=floatval($cval);
        if(is_int($value))
            $cval=intval($cval);
        if($cval!=$value) {
            if($verbose)
                printf("\n [MySQL] $varID: {$cval} -> {$value}");
            static::Execute("SET SESSION $varID=?",array($value));
         }
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

    public static function PageExecute($sql, $results, $pagenum, array $args = array())
    {
        $sql = static::FixSQL($sql);
        $args = static::FixArgs($args);
        return static::$conn->PageExecute($sql, $results, $pagenum, $args);
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

    public static function SetFetchMode($arg)
    {
        return static::$conn->SetFetchMode($arg);
    }

    public static function StartTrans(array $args = array()) { return static::$conn->StartTrans(); }
    public static function CompleteTrans(array $args = array()) { return static::$conn->CompleteTrans(); }

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

    public static function ExecuteRandomRows(string $table, string $pk='id', int $nrows=1) {
        // http://www.mysqltutorial.org/select-random-records-database-table.aspx
        $sql = <<<SQL
SELECT *
FROM $table AS t1
JOIN
    (SELECT CEIL(RAND() * (SELECT MAX($pk) FROM random)) AS $pk) AS t2
WHERE t1.$pk >= t2.$pk
ORDER BY t1.$pk ASC
LIMIT $nrows
SQL;
        return self::Execute($sql);
    }
}

class DBProxy
{
    static $SUPPORTED_FUNCTIONS = array('Execute', 'GetAll', 'GetOne', 'ErrorMsg', 'GetRow',"PageExecute");
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
        #var_dump($args);
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
