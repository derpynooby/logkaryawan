<?php
// ponytail: plain PDO. Upgrade: persistent connections under high concurrency.
$dsn = 'mysql:host='.($_ENV['DB_HOST']??'localhost').';dbname='.($_ENV['DB_NAME']??'log_karyawan').';charset=utf8mb4';
$db  = new PDO($dsn, $_ENV['DB_USER']??'root', $_ENV['DB_PASS']??'',
    [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
