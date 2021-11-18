<?php

namespace Taxjar\SalesTax\Test\Integration\Test\Stubs;

class LoggerStub extends \Taxjar\SalesTax\Model\Logger
{
    public function log($message, $label = ''): void
    {
        // Set member variables...
        if ($this->isRecording) {
            $this->playback[] = $message;
        }

        // Otherwise, do nothing...
    }
}
