<?php

namespace ETNA\FeatureContext;

use GuzzleHttp\Client;

class RabbitContext extends BaseContext
{
    public static $vhosts = ["/test-behat"];

    /**
     * @BeforeSuite
     */
    public static function loadEnv()
    {
        if (file_exists('./app/env/' . getenv('APPLICATION_ENV') . '.php')) {
            require_once './app/env/' . getenv('APPLICATION_ENV') . '.php';
        }
    }

    private static function getRabbitMqClient()
    {
        $rmq_url  = getenv("RABBITMQ_URL");
        $config   = parse_url($rmq_url);
        $base_uri = "{$config['scheme']}://{$config['host']}:15672";

        return new Client(
            [
                "base_uri" => $base_uri,
                "headers"  => ["Content-Type" => "application/json"],
                "auth"     => [$config["user"], $config["pass"]]
            ]
        );
    }

    /**
     * @BeforeSuite
     */
    public static function createVhosts()
    {
        $client = self::getRabbitMqClient();

        foreach (self::$vhosts as $vhost) {
            $vhost = str_replace('/', '%2f', $vhost);

            $client->put("/api/vhosts/{$vhost}");
            $client->put(
                "/api/permissions/{$vhost}/guest",
                [
                    "json" => [
                        "configure" => ".*",
                        "write"     => ".*",
                        "read"      => ".*",
                    ]
                ]
            );
        }
    }

    /**
     * @AfterSuite
     */
    public static function deleteVhosts()
    {
        $client = self::getRabbitMqClient();

        foreach (self::$vhosts as $vhost) {
            $vhost = str_replace('/', '%2f', $vhost);
            $client->delete("/api/vhosts/{$vhost}");
        }
    }

    private function getNode($node, $response)
    {
        $nodes        = explode('/', $node);
        $response     = json_decode(json_encode($response), true);
        $current_node = $response;

        foreach ($nodes as $key_node => $node) {
            if (!in_array($node, array_keys($current_node))) {
                throw new \Exception("Node {$node} not found");
            }
            $current_node = $current_node[$node];
        }

        return $current_node;
    }

    /**
     * @Given /^"([^"]*)" devrait contenir "([^"]*)"(?: dans la file "([^"]*)"?)?$/
     */
    public function devraitContenir($node, $node_value, $queue_name = null)
    {
        $response = (null === $queue_name) ? $this->data : $this->response["{$queue_name}"][0];

        $current_node_value = json_encode($this->getNode($node, $response));

        $node_value         = str_replace('"', '', $node_value);
        $current_node_value = str_replace('"', '', $current_node_value);

        $this->check($node_value, $current_node_value, '', $errors);
        if ($nb_err = count($errors)) {
            throw new \Exception("{$nb_err} errors :\n" . implode("\n", $errors));
        }
    }

    /**
     * @Given /^"([^"]*)" devrait contenir (\d+) résultats(?: dans la file "([^"]*)")?$/
     */
    public function devraitContenirResultats($node, $length, $queue_name = null)
    {
        $response = (null === $queue_name) ? $this->data : $this->response["{$queue_name}"][0];

        $current_node = $this->getNode($node, $response);
        if ($length != count($current_node)) {
            throw new \Exception("Invalid node length " . count($current_node) . " != {$length}");
        }
    }

    /**
     * @Given /le producer "([^"]*)" devrait avoir publié un message dans la queue "([^"]*)"$/
     */
    public function leProducerDevraitAvoirPublieUnMessageDansLaQueue($producer, $queue)
    {
        $this->fetchMessage($producer, $queue);
    }

    /**
     * @Given /le producer "([^"]*)" devrait avoir publié un message dans la queue "([^"]*)" avec le corps contenu dans "([^"]*)"$/
     */
    public function leProducerDevraitAvoirPublieUnMessageDansLaQueueAvecLeCorpsContenuDans($producer, $queue, $body)
    {
        if (!file_exists($this->results_path . $body)) {
            throw new \Exception("File not found : {$this->results_path}${body}");
        }

        $body = file_get_contents($this->results_path . $body);
        $msg  = $this->fetchMessage($producer, $queue);

        if (json_decode($msg) != json_decode($body)) {
            throw new \Exception("{$msg} != {$body}");
        }
    }

    /**
     * @Given /le producer "([^"]*)" devrait avoir publié un message dans la queue "([^"]*)" en JSON$/
     */
    public function leProducerDevraitAvoirPublieUnMessageDansLaQueueEnJSON($producer, $queue, $body)
    {
        $msg = $this->fetchMessage($producer, $queue);

        if (!json_decode($msg)) {
            throw new \Exception("Invalid JSON message");
        }
    }

    private function fetchMessage($producer, $queue)
    {
        $channel = self::$silex_app["rabbit.producer"][$producer]->getChannel();
        $message = $channel->basic_get($queue, true);

        if (null === $message) {
            throw new \Exception("Queue {$queue} is empty");
        }

        return $message->body;
    }
}
