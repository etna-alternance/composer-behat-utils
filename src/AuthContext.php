<?php

namespace ETNA\FeatureContext;

use ETNA\FeatureContext as EtnaFeatureContext;

class AuthContext extends BaseContext
{
    private $request;
    static private $rsa = null;

    /**
     * @Given /je suis authentifié en tant que "([^"]*)"(?: depuis (\d+) minutes?)?(?: avec les roles "([^"]*)")?(?: avec l'id (\d+))?/
     */
    public function jeSuisAuthentifieEnTantQue($login, $duration = 1, $roles = "", $id = 1)
    {
        $duration = intval($duration);
        $id       = intval($id);

        $identity = base64_encode(
            json_encode(
                [
                    "id"         => $id,
                    "login"      => $login,
                    "logas"      => false,
                    "groups"     => explode(",", $roles),
                    "login_date" => date("Y-m-d H:i:s", strtotime("now -{$duration}minutes")),
                ]
            )
        );

        $identity = [
            "identity"  => $identity,
            "signature" => self::$rsa->sign($identity),
        ];

        $api_context = $this->getContext('ETNA\FeatureContext\ApiContext');
        $request     = $api_context->getRequest();

        // Set le cookie
        $request["cookies"]["authenticator"] = base64_encode(json_encode($identity));
        $api_context->setRequest($request);
    }

    /**
     * @Given /^je suis logas en tant que "([^"]*)" avec le compte "([^"]*)" et les roles "([^"]*)"$/
     */
    public function jeSuisLogasEnTantQueAvecLeCompteEtLesRoles($logas, $login, $roles)
    {
         $real_login = [
            "id"         => 1,
            "login"      => $login,
            "logas"      => false,
            "groups"     => explode(",", $roles),
            "login_date" => date("Y-m-d H:i:s"),
        ];

        $identity = [
            "id"         => 11,
            "login"      => $logas,
            "logas"      => $real_login,
            "groups"     => ["student"],
            "login_date" => date("Y-m-d H:i:s"),
        ];

        $identity = base64_encode(json_encode($identity));

        $identity = [
            "identity"  => $identity,
            "signature" => self::$rsa->sign($identity),
        ];

        $api_context = $this->getContext('ETNA\FeatureContext\ApiContext');
        $request     = $api_context->getRequest();

        // Set le cookie
        $request["cookies"]["authenticator"] = base64_encode(json_encode($identity));
        $api_context->setRequest($request);
    }

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
