<?php
// config.php
define('JWT_SECRET_KEY', $_ENV['JWT_SECRET_KEY'] ?? 'MA_SUPER_CLE_SECRETE');
define('JWT_ISSUER', $_ENV['JWT_ISSUER'] ?? 'http://localhost');
define('JWT_AUDIENCE', $_ENV['JWT_AUDIENCE'] ?? 'http://localhost');
define('JWT_EXPIRATION_TIME', (int) ($_ENV['JWT_EXPIRATION_TIME'] ?? 3600));
