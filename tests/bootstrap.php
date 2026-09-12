<?php

declare(strict_types=1);

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

$_SERVER['APP_ENV'] = 'test';
$_ENV['APP_ENV'] = 'test';

if (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
}

$jwtDirectory = dirname(__DIR__).'/var/test-jwt';
if (!is_dir($jwtDirectory)) {
    mkdir($jwtDirectory, 0777, true);
}

$privateKeyPath = $jwtDirectory.'/private.pem';
$publicKeyPath = $jwtDirectory.'/public.pem';
if (!file_exists($privateKeyPath) || !file_exists($publicKeyPath)) {
    $key = openssl_pkey_new(['private_key_type' => \OPENSSL_KEYTYPE_RSA, 'private_key_bits' => 2048]);
    if (false === $key || !openssl_pkey_export($key, $privateKey)) {
        throw new RuntimeException('Unable to generate the test JWT private key.');
    }

    $details = openssl_pkey_get_details($key);
    if (false === $details || !isset($details['key'])) {
        throw new RuntimeException('Unable to generate the test JWT public key.');
    }

    file_put_contents($privateKeyPath, $privateKey);
    file_put_contents($publicKeyPath, $details['key']);
}

foreach ([
    'DATABASE_URL' => 'sqlite:///'.dirname(__DIR__).'/data/database_test.sqlite',
    'JWT_SECRET_KEY' => $privateKeyPath,
    'JWT_PUBLIC_KEY' => $publicKeyPath,
    'JWT_PASSPHRASE' => '',
] as $name => $value) {
    $_ENV[$name] = $value;
    $_SERVER[$name] = $value;
    putenv($name.'='.$value);
}

if ($_SERVER['APP_DEBUG']) {
    umask(0000);
}
