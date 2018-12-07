<?php

namespace ETNA\FeatureContext;

use PhpAmqpLib\Connection\AMQPConnection;

use GuzzleHttp\Client;

class RabbitContext extends BaseContext
{
    public static $vhosts = ["/test-behat"];
    private $response;

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

            try {
                $client->delete("/api/vhosts/{$vhost}");
            } catch (\Exception $e) {
                // On ignore l'exception car le vhost n'existe peut être pas mais dans le doute on le supprime
            }

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

        $body = json_decode(file_get_contents($this->results_path . $body));
        $msg  = $this->fetchMessage($producer, $queue);

        $this->check($body, json_decode($msg), "result", $errors);
        if ($nb_err = count($errors)) {
            echo json_encode($msg, JSON_PRETTY_PRINT);
            throw new \Exception("{$nb_err} errors :\n" . implode("\n", $errors));
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

    /**
     * @Given /le producer "([^"]*)" publie un job avec le corps contenu dans "([^"]*)"/
     */
    public function leProducerPublieUnJobAvecLeCorpsContenuDans($producer, $body)
    {
        if (!file_exists($this->requests_path . $body)) {
            throw new \Exception("File not found : {$this->requests_path}${body}");
        }

        $body = json_decode(file_get_contents($this->requests_path . $body));
        if (false === isset(self::$silex_app['rabbit.producer'][$producer])) {
            throw new \Exception("Producer {$producer} not found");
        }

        $routing_key = self::$silex_app['rabbit.producers'][$producer]['queue_options']['routing_keys'][0];
        self::$silex_app['rabbit.producer'][$producer]->publish(json_encode($body), $routing_key);
    }

    /**
     * @Given /je traite (\d+) jobs avec le consumer "([^"]*)"/
     */
    public function jeTraiteJobsAvecLeConsumer($nb_jobs, $consumer)
    {
        if (false === isset(self::$silex_app['rabbit.consumer'][$consumer])) {
            throw new \Exception("Consumer {$consumer} not found");
        }

        self::$silex_app['rabbit.consumer'][$consumer]->consume($nb_jobs);
    }

    /**
     * @Then /^il doit y avoir un message dans la file "([^"]*)"$/
     */
    public function ilDoitYAvoirUnMessageDansLaFile($queue = null)
    {
        $app     = self::$silex_app;
        $channel = $app["rabbit.connection"]['default']->channel();

        $response_msg    = $channel->basic_get($queue);
        $parsed_response = json_decode($response_msg->body);
        if (empty($parsed_response)) {
            throw new \Exception("{$parsed_response}");
        }
    }

    /**
     * @Then /^il doit y avoir un message dans la file "([^"]*)" avec le corps contenu dans "([^"]*)"$/
     */
    public function ilDoitYAvoirUnMessageDansLaFileAvecLeCorpsContenuDans($queue = null, $body = null)
    {
        if (null !== $body && !file_exists($this->results_path . $body)) {
            throw new \Exception("File not found : {$this->results_path}${body}");
        }

        $body          = file_get_contents($this->results_path . $body);
        $parsed_wanted = json_decode($body);

        $app             = self::$silex_app;
        $channel         = $app["rabbit.connection"]['default']->channel();
        $response_msg    = $channel->basic_get($queue);
        $parsed_response = json_decode($response_msg->body);

        $this->response[$queue] = $parsed_response;

        $this->check($parsed_wanted, $parsed_response, "result", $errors);
        $this->handleErrors($parsed_response, $errors);
    }

    /**
     * @Then la queue ":queue_name" devrait être vide
     */
    public function laQueueDevraitEtreVide($queue_name)
    {
        $channel = self::$silex_app["rabbit.connection"]['default']->channel();

        list($queue, $message_count, $consumer_count) = $channel->queue_declare($queue_name, true);

        if (0 !== $message_count) {
            throw new \Exception("Expecting {$queue_name} to be empty, but found {$message_count} job(s)");
        }
    }
}
