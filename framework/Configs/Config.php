<?php

namespace Framework\Configs;

class Config
{
    private $configArray = array();

    public function __construct()
    {
        $configs = array_values(array_diff(scandir(__APP_ROOT__ . "/../config"), array('.', '..')));

        foreach($configs as $config)
        {
            $this->configArray[rtrim($config, ".php")] = require(__APP_ROOT__ . "/../config/" . $config);
        }
    }

    public function getAllConfigs()
    {
        return $this->configArray;
    }

    public function getConfig($file)
    {
        if(array_key_exists($file, $this->configArray) == false)
        {
            $file .= ".exemple";
            return $this->configArray[$file];
        }
        return $this->configArray[$file];
    }
}
