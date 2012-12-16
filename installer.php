#!/usr/bin/env php
<?php

/*
 * This file is part of Magerun.
 * File was copied from composer project
 *
 * (c) Nils Adermann <naderman@naderman.de>
 *     Jordi Boggiano <j.boggiano@seld.be>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

process($argv);

/**
 * processes the installer
 */
function process($argv)
{
    $check      = in_array('--check', $argv);
    $help       = in_array('--help', $argv);
    $force      = in_array('--force', $argv);
    $quiet      = in_array('--quiet', $argv);
    $installDir = false;

    foreach ($argv as $key => $val) {
        if (0 === strpos($val, '--install-dir')) {
            if (13 === strlen($val) && isset($argv[$key+1])) {
                $installDir = trim($argv[$key+1]);
            } else {
                $installDir = trim(substr($val, 14));
            }
        }
    }

    if ($help) {
        displayHelp();
        exit(0);
    }

    $ok = checkPlatform($quiet);

    if (false !== $installDir && !is_dir($installDir)) {
        out("The defined install dir ({$installDir}) does not exist.", 'info');
        $ok = false;
    }

    if ($check) {
        exit($ok ? 0 : 1);
    }

    if ($ok || $force) {
        installMagerun($installDir, $quiet);
        exit(0);
    }

    exit(1);
}

/**
 * displays the help
 */
function displayHelp()
{
    echo <<<EOF
Magerun Installer
------------------
Options
--help               this help
--check              for checking environment only
--force              forces the installation
--install-dir="..."  accepts a target installation directory

EOF;
}

/**
 * check the platform for possible issues on running composer
 */
function checkPlatform($quiet)
{
    $errors = array();
    $warnings = array();

    $iniPath = php_ini_loaded_file();
    $displayIniMessage = false;
    if ($iniPath) {
        $iniMessage = PHP_EOL.PHP_EOL.'The php.ini used by your command-line PHP is: ' . $iniPath;
    } else {
        $iniMessage = PHP_EOL.PHP_EOL.'A php.ini file does not exist. You will have to create one.';
    }

    if (ini_get('detect_unicode')) {
        $errors['unicode'] = 'On';
    }

    if (extension_loaded('suhosin')) {
        $suhosin = ini_get('suhosin.executor.include.whitelist');
        $suhosinBlacklist = ini_get('suhosin.executor.include.blacklist');
        if (false === stripos($suhosin, 'phar') && (!$suhosinBlacklist || false !== stripos($suhosinBlacklist, 'phar'))) {
            $errors['suhosin'] = $suhosin;
        }
    }

    if (!extension_loaded('Phar')) {
        $errors['phar'] = true;
    }

    if (!ini_get('allow_url_fopen')) {
        $errors['allow_url_fopen'] = true;
    }

    if (extension_loaded('ionCube Loader') && ioncube_loader_iversion() < 40009) {
        $errors['ioncube'] = ioncube_loader_version();
    }

    if (version_compare(PHP_VERSION, '5.3.2', '<')) {
        $errors['php'] = PHP_VERSION;
    }

    if (version_compare(PHP_VERSION, '5.3.4', '<')) {
        $warnings['php'] = PHP_VERSION;
    }

    if (!extension_loaded('openssl')) {
        $warnings['openssl'] = true;
    }

    if (ini_get('apc.enable_cli')) {
        $warnings['apc_cli'] = true;
    }

    ob_start();
    phpinfo(INFO_GENERAL);
    $phpinfo = ob_get_clean();
    if (preg_match('{Configure Command(?: *</td><td class="v">| *=> *)(.*?)(?:</td>|$)}m', $phpinfo, $match)) {
        $configure = $match[1];

        if (false !== strpos($configure, '--enable-sigchild')) {
            $warnings['sigchild'] = true;
        }

        if (false !== strpos($configure, '--with-curlwrappers')) {
            $warnings['curlwrappers'] = true;
        }
    }

    if (!empty($errors)) {
        out("Some settings on your machine make Magerun unable to work properly.", 'error');

        out('Make sure that you fix the issues listed below and run this script again:', 'error');
        foreach ($errors as $error => $current) {
            switch ($error) {
                case 'phar':
                    $text = PHP_EOL."The phar extension is missing.".PHP_EOL;
                    $text .= "Install it or recompile php without --disable-phar";
                    break;

                case 'unicode':
                    $text = PHP_EOL."The detect_unicode setting must be disabled.".PHP_EOL;
                    $text .= "Add the following to the end of your `php.ini`:".PHP_EOL;
                    $text .= "    detect_unicode = Off";
                    $displayIniMessage = true;
                    break;

                case 'suhosin':
                    $text = PHP_EOL."The suhosin.executor.include.whitelist setting is incorrect.".PHP_EOL;
                    $text .= "Add the following to the end of your `php.ini` or suhosin.ini (Example path [for Debian]: /etc/php5/cli/conf.d/suhosin.ini):".PHP_EOL;
                    $text .= "    suhosin.executor.include.whitelist = phar ".$current;
                    $displayIniMessage = true;
                    break;

                case 'php':
                    $text = PHP_EOL."Your PHP ({$current}) is too old, you must upgrade to PHP 5.3.2 or higher.";
                    break;

                case 'allow_url_fopen':
                    $text = PHP_EOL."The allow_url_fopen setting is incorrect.".PHP_EOL;
                    $text .= "Add the following to the end of your `php.ini`:".PHP_EOL;
                    $text .= "    allow_url_fopen = On";
                    $displayIniMessage = true;
                    break;

                case 'ioncube':
                    $text = PHP_EOL."Your ionCube Loader extension ($current) is incompatible with Phar files.".PHP_EOL;
                    $text .= "Upgrade to ionCube 4.0.9 or higher or remove this line (path may be different) from your `php.ini` to disable it:".PHP_EOL;
                    $text .= "    zend_extension = /usr/lib/php5/20090626+lfs/ioncube_loader_lin_5.3.so";
                    $displayIniMessage = true;
                    break;
            }
            if ($displayIniMessage) {
                $text .= $iniMessage;
            }
            out($text, 'info');
        }

        out('');
        return false;
    }

    if (!empty($warnings)) {
        out("Some settings on your machine may cause stability issues with n98-magerun.", 'error');

        out('If you encounter issues, try to change the following:', 'error');
        foreach ($warnings as $warning => $current) {
            switch ($warning) {
                case 'apc_cli':
                    $text = PHP_EOL."The apc.enable_cli setting is incorrect.".PHP_EOL;
                    $text .= "Add the following to the end of your `php.ini`:".PHP_EOL;
                    $text .= "    apc.enable_cli = Off";
                    $displayIniMessage = true;
                    break;

                case 'sigchild':
                    $text = PHP_EOL."PHP was compiled with --enable-sigchild which can cause issues on some platforms.".PHP_EOL;
                    $text .= "Recompile it without this flag if possible, see also:".PHP_EOL;
                    $text .= "    https://bugs.php.net/bug.php?id=22999";
                    break;

                case 'curlwrappers':
                    $text = PHP_EOL."PHP was compiled with --with-curlwrappers which will cause issues with HTTP authentication and GitHub.".PHP_EOL;
                    $text .= "Recompile it without this flag if possible";
                    break;

                case 'openssl':
                    $text = PHP_EOL."The openssl extension is missing, which will reduce the security and stability of Magerun.".PHP_EOL;
                    $text .= "If possible you should enable it or recompile php with --with-openssl";
                    break;

                case 'php':
                    $text = PHP_EOL."Your PHP ({$current}) is quite old, upgrading to PHP 5.3.4 or higher is recommended.".PHP_EOL;
                    $text .= "Magerun works with 5.3.2+ for most people, but there might be edge case issues.";
                    break;
            }
            if ($displayIniMessage) {
                $text .= $iniMessage;
            }
            out($text, 'info');
        }

        out('');
        return true;
    }

    if (!$quiet) {
        out("All settings correct for using Magerun", 'success');
    }
    return true;
}

/**
 * installs composer to the current working directory
 */
function installMagerun($installDir, $quiet)
{
    $installPath = (is_dir($installDir) ? rtrim($installDir, '/').'/' : '') . 'n98-magerun.phar';
    $installDir = realpath($installDir) ? realpath($installDir) : getcwd();
    $file       = $installDir.DIRECTORY_SEPARATOR.'n98-magerun.phar';

    if (is_readable($file)) {
        @unlink($file);
    }

    $retries = 3;
    while ($retries--) {
        if (!$quiet) {
            out("Downloading...", 'info');
        }

        $source = (extension_loaded('openssl') ? 'https' : 'http').'://raw.github.com/netz98/n98-magerun/master/n98-magerun.phar';
        $errorHandler = new ErrorHandler();
        set_error_handler(array($errorHandler, 'handleError'));
        if (!copy($source, $file, getStreamContext())) {
            out('Download failed: '.$errorHandler->message, 'error');
        }
        restore_error_handler();
        if ($errorHandler->message) {
            continue;
        }

        try {
            // test the phar validity
            $phar = new Phar($file);
            // free the variable to unlock the file
            unset($phar);
            break;
        } catch (Exception $e) {
            if (!$e instanceof UnexpectedValueException && !$e instanceof PharException) {
                throw $e;
            }
            unlink($file);
            if ($retries) {
                if (!$quiet) {
                   out('The download is corrupt, retrying...', 'error');
                }
            } else {
                out('The download is corrupt ('.$e->getMessage().'), aborting.', 'error');
                exit(1);
            }
        }
    }

    if ($errorHandler->message) {
        out('The download failed repeatedly, aborting.', 'error');
        exit(1);
    }

    chmod($file, 0755);

    if (!$quiet) {
        out(PHP_EOL."Magerun successfully installed to: " . $file, 'success', false);
        out(PHP_EOL."Use it: php $installPath", 'info');
    }
}

/**
 * colorize output
 */
function out($text, $color = null, $newLine = true)
{
    if (DIRECTORY_SEPARATOR == '\\') {
        $hasColorSupport = false !== getenv('ANSICON');
    } else {
        $hasColorSupport = true;
    }

    $styles = array(
        'success' => "\033[0;32m%s\033[0m",
        'error' => "\033[31;31m%s\033[0m",
        'info' => "\033[33;33m%s\033[0m"
    );

    $format = '%s';

    if (isset($styles[$color]) && $hasColorSupport) {
        $format = $styles[$color];
    }

    if ($newLine) {
        $format .= PHP_EOL;
    }

    printf($format, $text);
}

/**
 * function copied from Magerun\Util\StreamContextFactory::getContext
 *
 * Any changes should be applied there as well, or backported here.
 */
function getStreamContext()
{
    $options = array('http' => array());

    // Handle system proxy
    if (!empty($_SERVER['HTTP_PROXY']) || !empty($_SERVER['http_proxy'])) {
        // Some systems seem to rely on a lowercased version instead...
        $proxy = parse_url(!empty($_SERVER['http_proxy']) ? $_SERVER['http_proxy'] : $_SERVER['HTTP_PROXY']);
    }

    if (!empty($proxy)) {
        $proxyURL = isset($proxy['scheme']) ? $proxy['scheme'] . '://' : '';
        $proxyURL .= isset($proxy['host']) ? $proxy['host'] : '';

        if (isset($proxy['port'])) {
            $proxyURL .= ":" . $proxy['port'];
        } elseif ('http://' == substr($proxyURL, 0, 7)) {
            $proxyURL .= ":80";
        } elseif ('https://' == substr($proxyURL, 0, 8)) {
            $proxyURL .= ":443";
        }

        // http(s):// is not supported in proxy
        $proxyURL = str_replace(array('http://', 'https://'), array('tcp://', 'ssl://'), $proxyURL);

        if (0 === strpos($proxyURL, 'ssl:') && !extension_loaded('openssl')) {
            throw new \RuntimeException('You must enable the openssl extension to use a proxy over https');
        }

        $options['http'] = array(
            'proxy'           => $proxyURL,
            'request_fulluri' => true,
        );

        if (isset($proxy['user'])) {
            $auth = $proxy['user'];
            if (isset($proxy['pass'])) {
                $auth .= ':' . $proxy['pass'];
            }
            $auth = base64_encode($auth);

            $options['http']['header'] = "Proxy-Authorization: Basic {$auth}\r\n";
        }
    }

    return stream_context_create($options);
}

class ErrorHandler
{
    public $message = '';

    public function handleError($code, $msg)
    {
        if ($this->message) {
            $this->message .= "\n";
        }
        $this->message .= preg_replace('{^copy\(.*?\): }', '', $msg);
    }
}
