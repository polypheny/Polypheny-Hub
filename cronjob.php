<?php
//this file should be executed once a month to get rid of inactive sessions

require_once("access.php");
$access = new Access();
$access->cronjob();
