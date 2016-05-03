<?php

namespace ETNA\FeatureContext;

use ETNA\FeatureContext as EtnaFeatureContext;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Behat\Hook\Scope\AfterStepScope;

class AuthContext extends BaseContext
{
    private $request;
    static private $rsa = null;

    /** @BeforeScenario */
    public function getRequest(BeforeScenarioScope $scope)
    {
        $environment = $scope->getEnvironment();

        $this->request = $environment->getContext('ETNA\FeatureContext\ApiContext')->getRequest();
    }

    /**
     * @Given /^que je suis authentifié en tant que "([^"]*)"(?: depuis (\d+) minutes?)?(?: avec les roles "([^"]*)")?(?: avec l'id (\d+))?/
     */
    public function queJeSuisAuthentifieEnTantQue($login, $duration = 1, $roles = "", $id = 1)
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

        $this->request["cookies"]["authenticator"] = base64_encode(json_encode($identity));
    }

    /** @AfterStep */
    public function setRequest(AfterStepScope $scope)
    {
        $environment = $scope->getEnvironment();

        $environment->getContext('ETNA\FeatureContext\ApiContext')->setRequest($this->request);
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
