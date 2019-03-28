<?php

namespace ETNA\FeatureContext;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

use ETNA\FeatureContext\BaseContext;

/**
 * Defines application features from the specific context.
 */
class CommandContext extends BaseContext
{

    public function __construct()
    {
    }

    /**
     * @Given je lance la commande ":command_name" avec les paramêtres contenus dans :command_params
     * @Given je lance la commande ":command_name"
     */
    public function jeLanceLaCommande($command_name, $command_params = null)
    {
        $application = new Application($this->getKernel());
        $command     = $application->find($command_name);
        $tester      = new CommandTester($command);
        $params      = [];

        if (null !== $command_params) {
            $params = json_decode(file_get_contents($this->requests_path . "/" . $command_params), true);
        }

        $this->tester = $tester;
        $this->getContext("ETNA\FeatureContext\ExceptionContainerContext")->try(
            function () use ($tester, $params) {
                $tester->execute($params);
            }
        );
    }

    /**
     * @Given la sortie de la commande devrait être identique à ":output_file"
     */
    public function laSortieDeLaCommandeDevraitEtreIdentiqueA($output_file)
    {
        $expected = file_get_contents($this->results_path . "/" . $output_file);

        if ($expected !== $this->tester->getDisplay()) {
            throw new \Exception("Unmaching command outputs : ===\n{$this->tester->getDisplay()}\n===\n$expected\n===\n");
        }
    }

    /**
     * @Given la commande ":command_name" devrait exister
     */
    public function laCommandeDevraitExister($command_name)
    {
        $application = new Application($this->getKernel());

        // Ca throw une exception si la commande n'existe pas
        $application->find($command_name);
    }
}
