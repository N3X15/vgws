<?php
namespace VGWS\Database\Compatibility;
/**
 * Compatibility layer for Postgres.
 */
class PostgresCompatibility extends \VGWS\Database\Compatibility\DBCompatibility
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
