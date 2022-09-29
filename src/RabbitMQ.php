<?php

namespace ETNA\FeatureContext;

use GuzzleHttp\Client;

trait RabbitMQ
{
    static private function getRabbitMqClient()
    {
        $base_url = getenv("RABBITMQ_MGMT_URL") ?: "http://localhost:15672";
        $user = getenv("RABBITMQ_USER") ?: "guest";
        $password = getenv("RABBITMQ_PASS") ?: "guest"; 

        return new Client([
            "base_uri" => $base_url,
            "headers" => ["Content-Type" => "application/json"],
            "auth"    => [$user, $password]
        ]);
    }

    /**
     * @BeforeSuite
     */
    static public function createVhosts()
    {
        $client = self::getRabbitMqClient();

        foreach (self::$vhosts as $vhost) {
            $vhost = str_replace('/', '%2f', $vhost);

	    // delete the vhost if it exists. See the comment bellow in deleteVhosts()
            $client->delete("/api/vhosts/{$vhost}", [ 'http_errors' => false ]);

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
    static public function deleteVhosts()
    {
        // 2022-04: we do not want to delete the vhost(s) from inside the
        // test-suite. It has unwanted side-effect: when a vhost is deleted,
        // rabbitmq closes all client connections connected to that vhost.
        // Then later when php garbage collects the rabbitmq clients and they
        // try to properly close connections, we got exceptions that causes
        // the test-suite to fail and exit(-1).
        // The proper way would be to close all connections we delete the
        // vhost, but their lifecycle is managed outside the test-suite,
        // and it is way easier to just delete the vhosts outside the
        // test-suite, once behat exits.
        
        // $client = self::getRabbitMqClient();
        // foreach (self::$vhosts as $vhost) {
        //     $vhost = str_replace('/', '%2f', $vhost);
        //     $client->delete("/api/vhosts/{$vhost}");
        // }
    }
}
