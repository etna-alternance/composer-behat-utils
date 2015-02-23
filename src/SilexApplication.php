<?php

namespace ETNA\FeatureContext;

trait SilexApplication
{
    static private $silex_app;

    /**
     * @BeforeSuite
     */
    static public function setupSilexApplication()
    {
        if (file_exists(getcwd() . "/app/bootstrap.php")) {
            $api_path = getcwd() . "/app/bootstrap.php";
        } else {
            $api_path = getcwd() . "/public/index.php";
        }

        self::$silex_app = include_once $api_path;
        global $app;
        $app = self::$silex_app;
    }
}
