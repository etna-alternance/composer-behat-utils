<?php

namespace ETNA\FeatureContext;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class ApiContext extends BaseContext
{
    private $base_url;
    private $request;

    /**
     * Initializes context.
     * Every scenario gets it's own context object.
     */
    public function __construct()
    {
        $this->base_url = "http://localhost:8080";
        $this->request  = [
            "headers" => [],
            "cookies" => [],
            "files"   => [],
        ];
    }

    public function getRequest()
    {
        return $this->request;
    }

    public function setRequest($request)
    {
        $this->request = $request;
    }

    /**
     * @When /^je fais un (GET|POST|PUT|DELETE|OPTIONS) sur ((?:[a-zA-Z0-9,:!\/\.\?\&\=\+_%-]*)|"(?:[^"]+)")(?: avec le corps contenu dans "([^"]*\.json)")?$/
     */
    public function jeFaisUneRequetteHTTP($method, $url, $body = null)
    {
        if ($body !== null) {
            $body = file_get_contents($this->requests_path . $body);
            if (!$body) {
                throw new Exception("File not found : {$this->requests_path}${body}");
            }
        }
        $this->jeFaisUneRequetteHTTPAvecDuJSON($method, $url, $body);
    }

    /**
     * @When /^je fais un (GET|POST|PUT|DELETE|OPTIONS) sur ((?:[a-zA-Z0-9,:!\/\.\?\&\=\+_%-]*)|"(?:[^"]+)") avec le JSON suivant :$/
     */
    public function jeFaisUneRequetteHTTPAvecDuJSON($method, $url, $body)
    {
        if (preg_match('/^".*"$/', $url)) {
            $url = substr($url, 1, -1);
        }

        if ($body !== null) {
            if (is_object($body)) {
                $body = $body->getRaw();
            }
            $this->request["headers"]["Content-Type"] = 'application/json';
            // add content-length ...
        }

        $request = Request::create($this->base_url . $url, $method, [], [], [], [], $body);
        $request->headers->add($this->request["headers"]);
        $request->cookies->add($this->request["cookies"]);
        $request->files->add($this->request["files"]);

        $response = self::$silex_app->handle($request, HttpKernelInterface::MASTER_REQUEST, true);

        $result = [
            "http_code"    => $response->getStatusCode(),
            "http_message" => Response::$statusTexts[$response->getStatusCode()],
            "body"         => $response->getContent(),
            "headers"      => array_map(
                function ($item) {
                    return $item[0];
                },
                $response->headers->all()
            ),
        ];

        $this->getContext('ETNA\FeatureContext\DoctrineContext')->checkMaxQueries($method, $result);

        $this->response = $result;
    }

    /**
     * @Then /^le status HTTP devrait être (\d+)$/
     */
    public function leStatusHTTPDevraitEtre($code)
    {
        $retCode = $this->response["http_code"];
        if ("$retCode" !== "$code") {
            echo $this->response["body"];
            throw new Exception("Bad http response code {$retCode} != {$code}");
        }
    }

    /**
     * @Then /^le message HTTP devrait être "([^"]*)"$/
     */
    public function leMessageHTTPDevraitEtre($mess)
    {
        if (trim($this->response['http_message']) != "$mess") {
            throw new Exception("Bad message response message {$this->response['http_message']} != {$mess}");
        }
    }

    /**
     * @Then /^je devrais avoir un résultat d\'API en JSON$/
     */
    public function jeDevraisAvoirUnResultatDApiEnJSON()
    {
        if ("application/json" !== $this->response["headers"]["content-type"]) {
            throw new Exception("Invalid response type");
        }
        if ("" == $this->response['body']) {
            throw new Exception("No response");
        }
        $json = json_decode($this->response['body']);

        if (null === $json && json_last_error()) {
            throw new Exception("Invalid response");
        }
        $this->data = $json;
    }

    /**
     * @Given /^je devrais avoir un résultat d\'API en PDF$/
     */
    public function jeDevraisAvoirUnResultatDApiEnPdf()
    {
        if ("application/pdf" !== $this->response["headers"]["content-type"]) {
            throw new Exception("Invalid response type");
        }
    }

    /**
     * @Given /^je devrais avoir un résultat d\'API en CSV$/
     */
    public function jeDevraisAvoirUnResultatDApiEnCsv()
    {
        if ("text/csv; charset=UTF-8" !== $this->response["headers"]["content-type"]) {
            throw new Exception("Invalid response type");
        }
    }

     /**
     * @Given /^le nom du csv devrait être "(.*)"$/
     */
    public function leNomDuCsvDevraitEtre($filename)
    {
        if (preg_match('/.*?filename="(.*)".*?/', $this->response["headers"]["content-disposition"], $matches)) {
            if ($matches[1] !== $filename) {
                $this->check($filename, $matches[1], "result", $errors);
                $this->handleErrors($matches[1], $errors);
            }
        } else {
            throw new Exception("Invalid filename");
        }
    }

    /**
     * @Given /^le header "([^"]*)" doit être "([^"]*)"$/
     */
    public function leHeaderDoitEtre($header, $value)
    {
        if ($this->response["headers"][strtolower($header)] != $value) {
            throw new Exception("Invalid header '{$header}'. Value should be '{$value}' but received '{$this->response["headers"][$header]}'");
        }
    }

    /**
     * @Then /^je devrais avoir un tableau de résultats(?: de (\d+) éléments?)?$/
     */
    public function jeDevraisAvoirUnTableauDeResultatsDeElements($length = null)
    {
        if (0 == $length && is_object($this->data)) {
            return;
        }
        if (!is_array($this->data)) {
            throw new Exception("Response is not an array");
        }
        if (null !== $length) {
            if (count($this->data) != $length) {
                throw new Exception("Invalid response length " . count($this->data) . " != {$length}");
            }
        }
    }

    /**
     * @Then /^le résultat devrait être identique à "(.*)"$/
     * @Then /^le résultat devrait être identique au JSON suivant :$/
     * @Then /^le résultat devrait ressembler au JSON suivant :$/
     *
     * @param string $string
     */
    public function leResultatDevraitRessemblerAuJsonSuivant($string)
    {
        $result = json_decode($string);
        if (null === $result) {
            throw new Exception("json_decode error");
        }

        $this->check($result, $this->data, "result", $errors);
        $this->handleErrors($this->data, $errors);
    }

    /**
     * @Then /^le résultat devrait être identique au fichier "(.*)"$/
     */
    public function leResultatDevraitRessemblerAuFichier($file)
    {
        $file = realpath($this->results_path . "/" . $file);
        $this->leResultatDevraitRessemblerAuJsonSuivant(file_get_contents($file));
    }

    /**
     * @Then /^je devrais avoir un objet comme résultat$/
     */
    public function jeDevraisAvoirUnObjetCommeResultat()
    {
        if (!is_object($this->data)) {
            throw new Exception("{$this->data} is not an object");
        }
    }
}
