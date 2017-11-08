<?php

include './config_global.php';

error_reporting(E_ALL);

include DISCUZ_ROOT.'./global.func.php';

include DISCUZ_ROOT.'./class_template.php';

$a = 'demo';

$b = array(1=>'a',2=>'b',3=>'c');

include template('demo/demo');
