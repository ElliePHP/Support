<?php

use ElliePHP\Components\Support\Constants\HashAlgos;
use ElliePHP\Components\Support\Util\Hash;

require_once 'vendor/autoload.php';


echo Hash::hash('33333', HashAlgos::XXH3);