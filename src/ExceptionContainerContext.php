<?php

namespace ETNA\FeatureContext;

use Behat\Behat\Context\Context;

/**
 * Contexte behat permettant la gestion et le catch des exceptions.
 *
 * On s'en sert pour recueillir d'éventuelles exceptions et pouvoir faire des tests dessus.
 */
class ExceptionContainerContext implements Context
{
    /** @var \Exception L'exception qu'on à catchée pour les tests */
    private $exception;

    /**
     * Constructeur de la classe
     */
    public function __construct()
    {
        $this->exception = null;
    }

    /**
     * Sette l'exception catchée
     *
     * @param \Exception $exception L'exception à stocker
     */
    public function setException(\Exception $exception): void
    {
        $this->exception = $exception;
    }

    /**
     * Step behat qui check que l'absence d'exception
     *
     * @Given /^ca devrait s'être bien déroulé$/
     */
    public function caDevraitSEtreBienDeroule(): void
    {
        if (null !== $this->exception) {
            throw new \Exception("Expecting last action to went well {$this->exception->getMessage()}");
        }
    }

    /**
     * Step behat qui check la présence d'une exception
     *
     * @Given /^ca ne devrait pas s'être bien déroulé$/
     */
    public function caNeDevraitPasBienSEtreDeroule(): void
    {
        if (null === $this->exception) {
            throw new \Exception("Was not expecting last action to went well");
        }
    }

    /**
     * Step behat qui check la contenu du message de l'exception stockée
     *
     * @param string $message Le contenu du message qu'on veut avoir dans l'exception
     *
     * @Given /^l'exception devrait avoir comme message "(.*)"$/
     */
    public function lExceptionDevraitAvoirCommeMessage($message): void
    {
        if (null === $this->exception) {
            throw new \Exception("Was expecting an exception to check message");
        }

        $expected = $this->exception->getMessage();
        if ($message !== $expected) {
            throw new Exception("Expecting exception to have {$message} but got {$expected}");
        }
    }
}
