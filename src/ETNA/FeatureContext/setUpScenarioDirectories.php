<?php

namespace ETNA\FeatureContext;

trait setUpScenarioDirectories
{
    /**
     * @BeforeScenario
     */
    public function setUpScenarioDirectories($event)
    {
        if ($event instanceof \Behat\Behat\Event\ScenarioEvent) {
            $path                = realpath(dirname($event->getScenario()->getFile())) . '/results/';
            $this->results_path  = $path;
            $requests            = realpath(dirname($event->getScenario()->getFile())) . '/requests/';
            $this->requests_path = $requests;
        } else {
            $path                = realpath(dirname($event->getOutline()->getFile())) . '/results/';
            $this->results_path  = $path;
            $requests            = realpath(dirname($event->getOutline()->getFile())) . '/requests/';
            $this->requests_path = $requests;
        }
    }
}
