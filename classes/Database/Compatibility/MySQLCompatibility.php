<?php
namespace VGWS\Database\Compatibility;
/**
 * Compatibility layer for MySQL/MariaDB.
 */
class MySQLCompatibility extends \VGWS\Database\Compatibility\DBCompatibility
{
    public $HighPriority = 'HIGH_PRIORITY';
    public $IdentEscapeChar = '`'; // Works with ANSI
}
