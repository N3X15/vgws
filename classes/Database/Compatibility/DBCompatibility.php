<?php
namespace VGWS\Database\Compatibility;
/**
 * All valid identifier escaping characters *that we want to fix*, as a string.
 */
define('ALL_IDENT_DELIMITERS', '`');

class DBCompatibility
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
                    if(substr($sql,$i,3)=='{P}') {
                        $i+=2;
                        $newsql.=KU_DBPREFIX;
                        continue;
                    }

                    // Check for TRUE
                    elseif(substr($sql,$i,3)=='TRUE') {
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
