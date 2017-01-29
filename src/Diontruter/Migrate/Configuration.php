<?php namespace Diontruter\Migrate;

/**
 * Encapsulates application configuration.
 *
 * @package Migrate
 * @author Dion Truter <dion@truter.org>
 */
class Configuration
{
    private $configs;

    /**
     * Configuration constructor, specifying the path to the configuration file to use.
     *
     * @param string $configPath The configuration path
     */
    public function __construct($configPath)
    {
        $this->configs = include $configPath;
    }

    /**
     * Get the value of a specified configuration name.
     *
     * @param string $name THe name of the configuration setting to get
     * @param mixed $default Default if the setting is not foun
     * @return mixed THe setting that was retrieved
     */
    public function getConfigValue($name, $default = null)
    {
        if (isset($this->configs[$name]) && !empty($this->configs[$name])) {
            return $this->configs[$name];
        }
        return $default;
    }

}
