<?php

$entityBody = file_get_contents('php://input');
file_put_contents("/www/php80/htdocs/logs/savRequest/savRequest_".date("Ymd")."_".date("His").".txt",$entityBody);