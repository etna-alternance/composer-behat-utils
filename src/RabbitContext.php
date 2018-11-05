<?php

namespace ETNA\FeatureContext;

use PhpAmqpLib\Connection\AMQPConnection;

use GuzzleHttp\Client;

class RabbitContext extends BaseContext
{
    public static $vhosts = ["/test-behat"];
    private $response;

    private static function getRabbitMqClient()
    {
        $base_uri = "http://localhost:15672";

        return new Client(
            [
                "base_uri" => $base_uri,
                "headers"  => ["Content-Type" => "application/json"],
                "auth"     => ["guest", "guest"]
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
        $channel = $this->getContainer()->get("old_sound_rabbit_mq.{$producer}_producer")->getChannel();
        $message = $channel->basic_get($queue, true);
        $channel->close();

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

        $body     = json_decode(file_get_contents($this->requests_path . $body));
        $producer = $this->getContainer()->get("old_sound_rabbit_mq.{$producer}_producer");

        $producer->publish(json_encode($body));
        $producer->getChannel()->close();
    }

    /**
     * @Given /je traite (\d+) jobs avec le consumer "([^"]*)"/
     */
    public function jeTraiteJobsAvecLeConsumer($nb_jobs, $consumer)
    {
        $consumer = $this->getContainer()->get("old_sound_rabbit_mq.{$consumer}_consumer");

        $consumer->consume($nb_jobs);
        $consumer->getChannel()->close();
    }

    /**
     * @Then /^il doit y avoir un message dans la file "([^"]*)"$/
     */
    public function ilDoitYAvoirUnMessageDansLaFile($queue = null)
    {
        $channel = $this->getContainer()->get("old_sound_rabbit_mq.connection.default")->channel();

        $response_msg    = $channel->basic_get($queue, true, null);
        $parsed_response = json_decode($response_msg->body);
        $channel->close();

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

        $channel         = $this->getContainer()->get("old_sound_rabbit_mq.connection.default")->channel();

        $response_msg    = $channel->basic_get($queue);
        $parsed_response = json_decode($response_msg->body);
        $channel->close();

        $this->response[$queue] = $parsed_response;

        $this->check($parsed_wanted, $parsed_response, "result", $errors);
        $this->handleErrors($parsed_response, $errors);
    }

    /**
     * @Then la queue ":queue_name" devrait être vide
     */
    public function laQueueDevraitEtreVide($queue_name)
    {
        $channel = $this->getContainer()->get("old_sound_rabbit_mq.connection.default")->channel();

        list($queue, $message_count, $consumer_count) = $channel->queue_declare($queue_name, true);

        $channel->close();

        if (0 !== $message_count) {
            throw new \Exception("Expecting {$queue_name} to be empty, but found {$message_count} job(s)");
        }
    }
}
