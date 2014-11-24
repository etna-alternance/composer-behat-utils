<?php

namespace ETNA\FeatureContext;

trait RSA
{
    static private $rsa = null;

    /**
     * @BeforeSuite
     */
    public static function setUpRsa()
    {
        $public_key = getcwd() . "/tmp/public-" . getenv("APPLICATION_ENV") . ".key";
        if (true === file_exists($public_key)) {
            unlink($public_key);
        }

        passthru("[ -d tmp/keys ] || mkdir -p tmp/keys", $return);
        if (0 !== $return) {
            throw new \Exception("Error with RSA : l." . (__LINE__ - 2));
        }
        passthru("[ -f tmp/keys/private.key ] || openssl genrsa  -out tmp/keys/private.key 2048", $return);
        if (0 !== $return) {
            throw new \Exception("Error with RSA : l." . (__LINE__ - 2));
        }
        passthru(
            "[ -f tmp/keys/public.key ]  || openssl rsa -in tmp/keys/private.key -pubout -out tmp/keys/public.key",
            $return
        );
        if (0 !== $return) {
            throw new \Exception("Error with RSA : l." . (__LINE__ - 4));
        }

        self::$rsa = \ETNA\RSA\RSA::loadPrivateKey("file://" . realpath(getcwd() . "/tmp/keys/private.key"));
    }

    /**
     * @AfterSuite
     */
    public static function tearDownRsa()
    {
        $file = getcwd() . "/tmp/public.key";
        if (true === file_exists($file)) {
            unlink($file);
        }
    }
}
