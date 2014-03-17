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
        self::$silex_app = require __DIR__ . "/../../../app/bootstrap.php";
        global $app;
        $app = self::$silex_app;
    }
}
