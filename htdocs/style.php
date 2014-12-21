<?php
require ('../config.php');
require ('../classes/classes.php');

$scss = new scssc();
new scss_compass($scss);
$scss->setFormatter("scss_formatter_compressed");
$scss->addImportPath(SCSS_DIR);

$server = new CMW_SCSS_Server(SCSS_DIR, null, $scss);
$server->serve();