#!/usr/bin/env php
<?php
/*
 * show information about latest phar files on https://files.magerun.net/
 */

$urls = <<<JSON_URLS
[
  {
    "channel": "unstable",
    "url":     "https://files.magerun.net/%basename%-dev.phar"
  },
  {
    "channel": "stable",
    "url":     "https://files.magerun.net/%basename%.phar"
  }
]
JSON_URLS;

$basename = '';
if (is_readable('build.xml') && $build = simplexml_load_file('build.xml')) {
    $basename = trim($build['name']);
}
$basename = $basename ?: 'n98-magerun';

$urls = strtr($urls, array('%basename%' => $basename));

$urlHeaders = function ($url) {
    return function ($name = null) use ($url) {
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

$box = function ($title) {
    $len = strlen($title);
    $buffer = str_repeat("=", $len + 4);
    $buffer .= "\n= $title =\n";
    $buffer .= str_repeat("=", $len + 4);

    return $buffer . "\n";
};

$bytes = function ($count) {
    return sprintf('%s (bytes)', number_format($count, 0, '.', ' '));
};

$urls = json_decode($urls, false, 16, null);

$main = function ($urls) use ($urlHeaders, $box, $bytes) {
    foreach ($urls as $url) {
        $title = sprintf("%s: %s", $url->channel, $url->url);
        echo $box($title);

        $headers = $urlHeaders($url->url);
        printf("Status..: %s\n", $headers(true));
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
        $magerunVersion = rtrim(`php -f "{$tempFile}" -- --no-ansi --version`);
        $md5File = md5_file($tempFile, false);
        printf("%'.-8s: %s\n          MD5.: %s\n", ucfirst($url->channel), $magerunVersion, $md5File);
        clearstatcache(null, $tempFile);
        printf("          Size: %s\n", $bytes(filesize($tempFile)));
        unlink($tempFile);
    }
};

$main($urls);
