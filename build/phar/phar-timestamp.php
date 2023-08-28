<?php
/**
 * update phar phar timestamp to the last commit in the repository for binary reproduceable build
 * of phar-files.
 *
 * @author Tom Klingenberg <https://github.com/ktomk>
 */

use Seld\PharUtils\Timestamps;

$projectDir = __DIR__ . '/../..';

if (!isset($_SERVER['argv'][1])) {
    echo sprintf("usage: %s <timestamp-to-set>\n", $_SERVER['argv'][0]);
    exit(1);
}

echo "reset phar phar timestamps to latest commit timestamp (reproducible builds)\n";
$timestamp = $_SERVER['argv'][1];

# seld/phar-utils via build requirements
require_once $projectDir . '/vendor/seld/phar-utils/src/Timestamps.php';

$pharFilepath = $projectDir . '/n98-magerun2.phar';

if (!is_file($pharFilepath) || !is_readable($pharFilepath)) {
    throw new RuntimeException(sprintf('Is not a phar or not readable: %s', var_export($pharFilepath, true)));
}

printf("Timestamp: %d (%s, date of commit)\n", $timestamp, date(DATE_RFC3339, $timestamp));
$threshold = 1343826993; # 2012-08-01T15:14:33Z
if ($timestamp < $threshold) {
    throw new RuntimeException(
        sprintf('Timestamp older than %d (%s).', $threshold, date(DATE_RFC3339, $threshold))
    );
}

$tmp = $pharFilepath . '.tmp';

if ($tmp !== $pharFilepath && file_exists($tmp)) {
    unlink($tmp);
}

if (!rename($pharFilepath, $tmp)) {
    throw new RuntimeException(
        sprintf('Failed to move phar %s to %s', var_export($pharFilepath, true), var_export($tmp, true))
    );
}

if (!is_file($tmp)) {
    throw new RuntimeException('No tempfile %s for reading', var_export($tmp, true));
}

$timestamps = new Timestamps($tmp);
$timestamps->updateTimestamps($timestamp);
$timestamps->save($pharFilepath, Phar::SHA512);

echo "SHA1.....: ", sha1_file($pharFilepath), "\nMD5......: ", md5_file($pharFilepath), "\n";

if (!unlink($tmp)) {
    throw new RuntimeException('Error deleting tempfile %s', var_export($tmp, true));
}
