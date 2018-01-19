<?php

namespace ETNA\FeatureContext;

use Mcustiel\Phiremock\Client\Phiremock;
use Behat\Testwork\Hook\Scope\BeforeSuiteScope;

class HttpApiMockContext extends BaseContext
{
    static private $phiremock;

    public function __construct($host = 'localhost', $port = 8080)
    {
        self::$phiremock = new Phiremock($host, $port);
    }

    /**
     * @AfterScenario @MockProxy
     */
    public static function clearProxyExpectations()
    {
        self::$phiremock->clearExpectations();
    }

    /**
     * @Then /^que le proxy "([^"]*)" effectue une requête (GET|POST|PUT|DELETE|OPTIONS) sur "((?:[a-zA-Z0-9,:!\/\.\?\&\=\+_%-]*)|"(?:[^"]+)")" et renvoie le status HTTP (\d+)(?: avec le résultat contenu dans "([^"]*\.json)")?$/
     */
    public function queLeProxyEffectueUneRequeteSurEtRenvoieLeStatusHTTP($proxy_name, $method, $url, $status_code, $body = null)
    {
        $response = self::prepareMockResponse($status_code, $body);

        $method_name = strtolower($method) . 'Request';

        // On set l'url et la méthode attendue par le mock
        $expectation = Phiremock::on(
            \Mcustiel\Phiremock\Client\Utils\A::{$method_name}()->andUrl(\Mcustiel\Phiremock\Client\Utils\Is::equalTo($url))
        )->then($response);

        // L'expectation attend la requête, la réponse est envoyée par le serveur phiremock
        self::$phiremock->createExpectation($expectation);
    }

    /**
     * @Then /^que le proxy "([^"]*)" effectue une requête (GET|POST|PUT|DELETE|OPTIONS) sur une url qui contient "((?:[a-zA-Z0-9,:!\/\.\?\&\=\+_%-]*)|"(?:[^"]+)")" et renvoie le status HTTP (\d+)(?: avec le résultat contenu dans "([^"]*\.json)")?$/
     */
    public function queLeProxyEffectueUneRequeteSurUneUrlQuiContientEtRenvoieLeStatusHTTP($proxy_name, $method, $url, $status_code, $body = null)
    {
        $response = self::prepareMockResponse($status_code, $body);

        $method_name = strtolower($method) . 'Request';

        // On set l'url  et la méthode attendue par le mock
        $expectation = Phiremock::on(
            \Mcustiel\Phiremock\Client\Utils\A::{$method_name}()->andUrl(\Mcustiel\Phiremock\Client\Utils\Is::containing($url))
        )->then($response);

        // L'expectation attend la requête, la réponse est envoyée par le serveur phiremock
        self::$phiremock->createExpectation($expectation);
    }

    /**
     * @Then /^que le proxy "([^"]*)" effectue une requête (GET|POST|PUT|DELETE|OPTIONS) sur une url qui match "([^"]*)" et renvoie le status HTTP (\d+)(?: avec le résultat contenu dans "([^"]*\.json)")?$/
     */
    public function queLeProxyEffectueUneRequeteSurUneUrlQuiMatchEtRenvoieLeStatusHTTP($proxy_name, $method, $url, $status_code, $body = null)
    {
        $response = self::prepareMockResponse($status_code, $body);

        $method_name = strtolower($method) . 'Request';

        // On set l'url depuis une regexp et la méthode attendue par le mock
        $expectation = Phiremock::on(
            \Mcustiel\Phiremock\Client\Utils\A::{$method_name}()->andUrl(\Mcustiel\Phiremock\Client\Utils\Is::matching($url))
        )->then($response);

        // L'expectation attend la requête, la réponse est envoyée par le serveur phiremock
        self::$phiremock->createExpectation($expectation);
    }

    private function prepareMockResponse($status_code, $body)
    {
        $response = \Mcustiel\Phiremock\Client\Utils\Respond::withStatusCode(intval($status_code))->andHeader('Content-Type', 'application/json');

        if (null !== $body) {
            $body = file_get_contents($this->results_path . $body);

            if (!$body) {
                throw new \Exception("File not found : {$this->results_path}${body}");
            }

            $response->andBody($body);
        }

        return $response;
    }
}
