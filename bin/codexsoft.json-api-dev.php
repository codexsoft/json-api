<?php

require_once __DIR__.'/findautoloader.php';

/**
 * @return string
 * @throws \Exception
 */
function unshiftFirstArgumentIfCorrectFilepath(): ?string
{
    if (PHP_SAPI !== 'cli') {
        throw new \Exception('Configuration loading works only from CLI');
    }

    if (count($_SERVER['argv']) < 2) {
        return null;
    }

    $configFile = realpath($_SERVER['argv'][1]);
    if (substr($configFile, -strlen('.php')) !== '.php') {
        return null;
    }

    if (!file_exists($configFile)) {
        throw new \Exception("config file $configFile does not exists!\n");
    }
    unset($_SERVER['argv'][1]);

    return $configFile;
}

/**
 * @return string
 * @throws Exception
 */
function getDefaultConfigFilepath(): string
{
    foreach ([
                 //__DIR__.'/../../../codexsoft.json-api.php',
                 __DIR__.'/../config/codexsoft.json-api.php',
             ] as $candidate) {
        if (\file_exists(realpath($candidate))) {
            return realpath($candidate);
        }
    }
    throw new \Exception('Default config file not exists!');
}

$jsonApiConfigFile = unshiftFirstArgumentIfCorrectFilepath() ?: getDefaultConfigFilepath();
require __DIR__.'/json-api-cli.php';