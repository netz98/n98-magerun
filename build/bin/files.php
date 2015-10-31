#!/usr/bin/env php
<?php
/*
 * show information about latest phar files on http://files.magerun.net/
 */

$urls = <<<JSON_URLS
[
  {
    "channel": "unstable",
    "url":     "http://files.magerun.net/n98-magerun-dev.phar"
  },
  {
    "channel": "stable",
    "url":     "http://files.magerun.net/n98-magerun-latest.phar"
  }
]
JSON_URLS;

$urlHeaders = function($url) {
    return function($name = null) use ($url) {
        static $response;
        $response || $response = shell_exec(sprintf('curl -sI %s', escapeshellarg($url)));

        if (null === $name) {
            return $response;
        }
        if (true === $name) {
            return explode("\n", $response, 2)[0];
        }

        $pattern = sprintf('~(*ANYCRLF)^%s:\s*(.*)$~mi', preg_quote($name, '~'));

        return preg_match($pattern, $response, $matches) ? $matches[1] : null;
    };
};

$box = function($title) {
    $len    = strlen($title);
    $buffer = str_repeat("=", $len + 4);
    $buffer .= "\n= $title =\n";
    $buffer .= str_repeat("=", $len + 4);
    return $buffer . "\n";
};

$bytes = function($count) {
    return sprintf('%s (bytes)', number_format($count, 0, '.', ' '));
};

$urls = json_decode($urls, false, 16, null);

$main = function($urls) use ($urlHeaders, $box, $bytes)
{
    foreach ($urls as $url) {
        $title = sprintf("%s: %s", $url->channel, $url->url);
        echo $box($title);

        $headers = $urlHeaders($url->url);
        printf("Status..: %s\n", $headers(TRUE));
        printf("Size....: %s\n", $bytes($headers('Content-Length')));
        printf("Modified: %s\n", $headers('Last-Modified'));

        echo "\n";
    }

    echo $box("Verify Phar-Files Versions");

    foreach ($urls as $url) {
        $tempFile = '.magerun-phar.~dl-temp-' . md5($url->url);
        if (file_exists($tempFile)) {
            unlink($tempFile);
        }
        file_put_contents($tempFile, fopen($url->url, 'r'));
        printf(
            "%'.-8s: %s\n          MD5.: %s\n", ucfirst($url->channel), rtrim(`php -f "{$tempFile}" -- --no-ansi --version`),
            md5_file($tempFile, false)
        );
        clearstatcache(null, $tempFile);
        printf("          Size: %s\n", $bytes(filesize($tempFile)));
        unlink($tempFile);
    }
};

$main($urls);
