<?php

namespace ETNA\FeatureContext;

trait RSA
{
    static private $rsa = null;

    /**
     * @BeforeSuite
     */
    public static function setUpRSA()
    {
        @unlink(__DIR__ . "/../../../tmp/public-" . getenv("APPLICATION_ENV") . ".key");

        passthru("[ -d tmp/keys ] || mkdir -p tmp/keys", $return);
        if ($return) {
            die("Error with RSA : l." . (__LINE__ - 2));
        }
        passthru("[ -f tmp/keys/private.key ] || openssl genrsa  -out tmp/keys/private.key 2048", $return);
        if ($return) {
            die("Error with RSA : l." . (__LINE__ - 2));
        }
        passthru("[ -f tmp/keys/public.key ]  || openssl rsa -in tmp/keys/private.key -pubout -out tmp/keys/public.key", $return);
        if ($return) {
            die("Error with RSA : l." . (__LINE__ - 2));
        }

        self::$rsa = \ETNA\RSA\RSA::loadPrivateKey(realpath(__DIR__ . "/../../../tmp/keys/private.key"));
    }

    /**
     * @AfterSuite
     */
    public static function tearDownRSA()
    {
        @unlink(__DIR__ . "/../../../tmp/public.key");
    }
}
