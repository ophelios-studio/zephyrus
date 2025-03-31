<?php namespace Zephyrus\Exceptions\Configuration;

class IdsThresholdInvalidException extends ConfigurationException
{
    public function __construct()
    {
        parent::__construct("Security IDS impact threshold property must be int (defaults to 50).", 18001);
    }
}
