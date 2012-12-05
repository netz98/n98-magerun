<?php

namespace N98\Util;

class Environment
{
    public function getHomeDirectory()
    {
        $config = Yaml::parse(__DIR__ . '../../config.yaml');
        if (isset($config['environment']['home'])) {
            return $config['environment']['home'];
        }

        if (getenv('HOME')) {
            return getenv('HOME');
        }

        throw new Exception("Wasn't able to determine the home directory.  This may be because the script is being executed by a different user than you're expecting.  Try setting the home directory in config.yaml");
    }
}