<?php

namespace ETNA\FeatureContext;

class RabbitContext extends BaseContext
{
    private $vhosts = ["/test-behat"];

    private static function getRabbitMqClient()
    {
        return new Client([
            "base_uri" => "http://127.0.0.1:15672",
            "headers"  => ["Content-Type" => "application/json"],
            "auth"     => ["guest", "guest"]
        ]);
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

    /**
     * @BeforeFeature @moulinetteJob
     */
    public static function bindMoulinetteQueue()
    {
        $channel = self::$silex_app["amqp.exchanges"]["default"]->getChannel();
        $channel->queue_declare("quest_moulinette", false, true, false, false)[0];
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
     * @Given /^il doit y avoir un message dans la file "([^"]*)"$/
     */
    public function ilDoitYAvoirUnMessageDansLaFile($queue = null)
    {
        self::$silex_app["amqp.queues"][$queue]->listen(
            function ($msg) {
                $msg->delivery_info['channel']->basic_cancel($msg->delivery_info['consumer_tag']);
                $body = json_decode($msg->body);
                if (empty($body)) {
                    throw new \Exception("{$msg->body}");
                }
            },
            false,
            false,
            false,
            false
        );
        $app["amqp.chans"]["default"]->wait();
    }

    /**
     * @Given /^il doit y avoir un message dans la file "([^"]*)" avec le corps contenu dans "([^"]*)"$/
    */
    public function ilDoitYAvoirUnMessageDansLaFileAvecLeCorpsContenuDans($queue = null, $body = null)
    {
        if (null !== $body) {
            if (!file_exists($this->results_path . $body)) {
                throw new \Exception("File not found : {$this->results_path}${body}");
            }
        }

        $body          = file_get_contents($this->results_path . $body);
        $parsed_wanted = json_decode($body);

        $channel = self::$silex_app["amqp.queues"][$queue]->getChannel();

        $response_msg    = $channel->basic_get($queue);
        $parsed_response = json_decode($response_msg->body);

        $this->response[$queue] = $parsed_response;

        $this->check($parsed_wanted, $parsed_response, "result", $errors);
        $this->handleErrors($parsed_response, $errors);
    }

    /**
     * @Given /^il doit y avoir un message dans la file "([^"]*)" en JSON$/
    */
    public function ilDoitYAvoirUnMessageDansLaFileEnJSON($queue = null)
    {
        $channel = self::$silex_app["amqp.queues"][$queue]->getChannel();

        $response_msg    = $channel->basic_get($queue);
        $parsed_response = json_decode($response_msg->body);

        $this->response[$queue] = $parsed_response;

        if (!$parsed_response) {
            throw new \Exception("Error: Queue {$queue} is empty");
        }
    }

}
