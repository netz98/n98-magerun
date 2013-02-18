<?php

namespace N98\Util;

use Symfony\Component\Yaml\Yaml;

class Environment
{
    public function getHomeDirectory()
    {
        $configPath = __DIR__ . '/../../../config.yaml';
        $config = Yaml::parse($configPath);
        if (isset($config['environment']['home'])) {
            return $config['environment']['home'];
        }

        if (getenv('HOME')) {
            return getenv('HOME');
        }

        throw new \Exception("Wasn't able to determine the home directory.  This may be because the script is being executed by a different user than you're expecting. Try setting the home directory in config.yaml ($configPath)");
    }
}