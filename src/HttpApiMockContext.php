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
        self::$phiremock->reset();
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

    /**
     * @Then que le proxy effectue une requête :method sur ":route" et renvoie le status HTTP :status avec l'image contenue dans ":image"
     */
    public function queLeProxyEffectueUneRequeteSurEtRenvoieStatusHTTPAvecLImageContenueDans($method, $route, $status, $image)
    {
        if (!file_exists($this->results_path . "/" . $image)) {
            throw new \Exception("Image not found");
        }

        $response = \Mcustiel\Phiremock\Client\Utils\Respond::withStatusCode(intval($status))
            ->andHeader('Content-Type', 'image/jpg')
            ->andBinaryBody(file_get_contents($this->results_path . "/" . $image));

        $method_name = strtolower($method) . 'Request';

        // On set l'url et la méthode attendue par le mock
        $expectation = Phiremock::on(
            \Mcustiel\Phiremock\Client\Utils\A::{$method_name}()->andUrl(\Mcustiel\Phiremock\Client\Utils\Is::equalTo($route))
        )->then($response);

        // L'expectation attend la requête, la réponse est envoyée par le serveur phiremock
        self::$phiremock->createExpectation($expectation);
    }

    /**
     * @Then /^que le proxy "([^"]*)" effectue une requête (GET|POST|PUT|DELETE|OPTIONS) sur "((?:[a-zA-Z0-9,:!\/\.\?\&\=\+_%-]*)|"(?:[^"]+)")" avec comme corps "([^"]*)" et renvoie le status HTTP (\d+)(?: avec le résultat contenu dans "([^"]*\.json)")?$/
     */
    public function queLeProxyEffectueUneRequeteSurAvecLeCorpsEtRenvoieLeStatusHTTP($proxy_name, $method, $url, $req_body, $status_code, $body = null)
    {
        $response = self::prepareMockResponse($status_code, $body);
        $req_body = self::prepareReqBody($req_body);

        $method_name = strtolower($method) . 'Request';

        // On set l'url et la méthode attendue par le mock
        $expectation = Phiremock::on(
            \Mcustiel\Phiremock\Client\Utils\A::{$method_name}()
                ->andUrl(\Mcustiel\Phiremock\Client\Utils\Is::equalTo($url))
                ->andBody(\Mcustiel\Phiremock\Client\Utils\Is::equalTo($req_body))
        )->then($response);

        // L'expectation attend la requête, la réponse est envoyée par le serveur phiremock
        self::$phiremock->createExpectation($expectation);
    }

    private function prepareReqBody($req_body) {
        $body = trim(file_get_contents($this->requests_path . $req_body));

        if (!$body) {
            throw new \Exception("File not found : {$this->requests_path}${body}");
        }

        return $body;
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

    /**
     * @Then le serveur de mock devrait avoir traité toutes les requêtes contenues dans ":scenario_file"
     */
    public function leServeurDeMockDevraitAvoirTraiteToutesLesRequetesContenuesDans($scenario_file)
    {
        $requests = [];
        $methods  = ['get', 'post', 'put', 'delete'];

        // Oui, on ne peut tout récupérer d'un coup..
        foreach ($methods as $method) {
            $function_name = "{$method}Request";
            $requests      = array_merge($requests, self::$phiremock->listExecutions(
                \Mcustiel\Phiremock\Client\Utils\A::{$function_name}()
            ));
        }

        $body = file_get_contents($this->results_path . $scenario_file);
        if (!$body) {
            throw new \Exception("File not found : {$this->results_path}${body}");
        }
        $body = json_decode($body);

        $this->check($body, $requests, "result", $errors);
        $this->handleErrors($requests, $errors);
    }
}
