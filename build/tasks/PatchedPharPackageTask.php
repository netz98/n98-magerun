<?php
/*
 * @author Tom Klingenberg <https://github.com/ktomk>
 * @license LGPL-3.0 <https://spdx.org/licenses/LGPL-3.0.html>
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information please see
 * <http://phing.info>.
 */

/**
 * Package task for {@link http://www.php.net/manual/en/book.phar.php Phar technology}.
 *
 * @package phing.tasks.ext
 * @author Alexey Shockov <alexey@shockov.com>
 * @since 2.4.0
 * @see PharPackageTask
 */
class PatchedPharPackageTask
    extends MatchingTask
{
    /**
     * @var PhingFile
     */
    private $destinationFile;

    /**
     * @var int
     */
    private $compression = Phar::NONE;

    /**
     * Base directory, from where local package paths will be calculated.
     *
     * @var PhingFile
     */
    private $baseDirectory;

    /**
     * @var PhingFile
     */
    private $cliStubFile;

    /**
     * @var PhingFile
     */
    private $webStubFile;

    /**
     * @var string
     */
    private $stubPath;

    /**
     * Private key the Phar will be signed with.
     *
     * @var PhingFile
     */
    private $key;

    /**
     * Password for the private key.
     *
     * @var string
     */
    private $keyPassword = '';

    /**
     * @var int
     */
    private $signatureAlgorithm = Phar::SHA1;

    /**
     * @var array
     */
    private $filesets = array();

    /**
     * @var PharMetadata
     */
    private $metadata = null;

    /**
     * @var string
     */
    private $alias;

    /**
     * @return PharMetadata
     */
    public function createMetadata()
    {
        return ($this->metadata = new PharMetadata());
    }

    /**
     * @return FileSet
     */
    public function createFileSet()
    {
        $this->fileset    = new IterableFileSet();
        $this->filesets[] = $this->fileset;

        return $this->fileset;
    }

    /**
     * Signature algorithm (md5, sha1, sha256, sha512, openssl),
     * used for this package.
     *
     * @param string $algorithm
     */
    public function setSignature($algorithm)
    {
        /*
         * If we don't support passed algprithm, leave old one.
         */
        switch ($algorithm) {
            case 'md5':
                $this->signatureAlgorithm = Phar::MD5;
                break;
            case 'sha1':
                $this->signatureAlgorithm = Phar::SHA1;
                break;
            case 'sha256':
                $this->signatureAlgorithm = Phar::SHA256;
                break;
            case 'sha512':
                $this->signatureAlgorithm = Phar::SHA512;
                break;
            case 'openssl':
                $this->signatureAlgorithm = Phar::OPENSSL;
                break;
            default:
                break;
        }
    }

    /**
     * Compression type (gzip, bzip2, none) to apply to the packed files.
     *
     * @param string $compression
     */
    public function setCompression($compression)
    {
        /**
         * If we don't support passed compression, leave old one.
         */
        switch ($compression) {
            case 'gzip':
                $this->compression = Phar::GZ;
                break;
            case 'bzip2':
                $this->compression = Phar::BZ2;
                break;
            default:
                break;
        }
    }

    /**
     * @return string
     */
    private function getCompressionLabel()
    {
        $compression = $this->compression;

        switch ($compression) {
            case Phar::GZ:
                return "gzip";

            case Phar::BZ2:
                return "bzip2";

            default:
                return sprintf("int(%d)", $compression);
        }
    }

    /**
     * Destination (output) file.
     *
     * @param PhingFile $destinationFile
     */
    public function setDestFile(PhingFile $destinationFile)
    {
        $this->destinationFile = $destinationFile;
    }

    /**
     * Base directory, which will be deleted from each included file (from path).
     * Paths with deleted basedir part are local paths in package.
     *
     * @param PhingFile $baseDirectory
     */
    public function setBaseDir(PhingFile $baseDirectory)
    {
        $this->baseDirectory = $baseDirectory;
    }

    /**
     * Relative path within the phar package to run,
     * if accessed on the command line.
     *
     * @param PhingFile $stubFile
     */
    public function setCliStub(PhingFile $stubFile)
    {
        $this->cliStubFile = $stubFile;
    }

    /**
     * Relative path within the phar package to run,
     * if accessed through a web browser.
     *
     * @param PhingFile $stubFile
     */
    public function setWebStub(PhingFile $stubFile)
    {
        $this->webStubFile = $stubFile;
    }

    /**
     * A path to a php file that contains a custom stub.
     *
     * @param string $stubPath
     */
    public function setStub($stubPath)
    {
        $this->stubPath = $stubPath;
    }

    /**
     * An alias to assign to the phar package.
     *
     * @param string $alias
     */
    public function setAlias($alias)
    {
        $this->alias = $alias;
    }

    /**
     * Sets the private key to use to sign the Phar with.
     *
     * @param PhingFile $key Private key to sign the Phar with.
     */
    public function setKey(PhingFile $key)
    {
        $this->key = $key;
    }

    /**
     * Password for the private key.
     *
     * @param string $keyPassword
     */
    public function setKeyPassword($keyPassword)
    {
        $this->keyPassword = $keyPassword;
    }

    /**
     * @throws BuildException
     */
    public function main()
    {
        $this->checkPreconditions();

        try {
            $this->log(
                'Building package: ' . $this->destinationFile->__toString(),
                Project::MSG_INFO
            );

            $baseDirectory = realpath($this->baseDirectory->getPath());

            try {
                $this->compressAllFiles($this->initPhar(), $baseDirectory);
            } catch (\RuntimeException $e) {
                $this->log('Most likely compression failed (known bug): ' . $e->getMessage());
                $this->compressEachFile($this->initPhar(), $baseDirectory);
            }
        } catch (Exception $e) {
            throw new BuildException(
                'Problem creating package: ' . $e->getMessage(),
                $e,
                $this->getLocation()
            );
        }
    }

    /**
     * @throws BuildException
     */
    private function checkPreconditions()
    {
        if (!extension_loaded('phar')) {
            throw new BuildException(
                "PharPackageTask require either PHP 5.3 or better or the PECL's Phar extension"
            );
        }

        if (is_null($this->destinationFile)) {
            throw new BuildException("destfile attribute must be set!", $this->getLocation());
        }

        if ($this->destinationFile->exists() && $this->destinationFile->isDirectory()) {
            throw new BuildException("destfile is a directory!", $this->getLocation());
        }

        if (!$this->destinationFile->canWrite()) {
            throw new BuildException("Can not write to the specified destfile!", $this->getLocation());
        }
        if (!is_null($this->baseDirectory)) {
            if (!$this->baseDirectory->exists()) {
                throw new BuildException(
                    "basedir '" . (string) $this->baseDirectory . "' does not exist!", $this->getLocation()
                );
            }
        }
        if ($this->signatureAlgorithm == Phar::OPENSSL) {

            if (!extension_loaded('openssl')) {
                throw new BuildException(
                    "PHP OpenSSL extension is required for OpenSSL signing of Phars!", $this->getLocation()
                );
            }

            if (is_null($this->key)) {
                throw new BuildException("key attribute must be set for OpenSSL signing!", $this->getLocation());
            }

            if (!$this->key->exists()) {
                throw new BuildException("key '" . (string) $this->key . "' does not exist!", $this->getLocation());
            }

            if (!$this->key->canRead()) {
                throw new BuildException("key '" . (string) $this->key . "' cannot be read!", $this->getLocation());
            }
        }
    }

    /**
     * Build and configure Phar object.
     *
     * @return Phar
     */
    private function buildPhar()
    {
        $phar = new Phar($this->destinationFile);

        if ($this->signatureAlgorithm == Phar::OPENSSL) {

            // Load up the contents of the key
            $keyContents = file_get_contents($this->key);

            // Setup an OpenSSL resource using the private key and tell the Phar
            // to sign it using that key.
            $private = openssl_pkey_get_private($keyContents, $this->keyPassword);
            $phar->setSignatureAlgorithm(Phar::OPENSSL, $private);

            // Get the details so we can get the public key and write that out
            // alongside the phar.
            $details = openssl_pkey_get_details($private);
            file_put_contents($this->destinationFile . '.pubkey', $details['key']);

        } else {
            $phar->setSignatureAlgorithm($this->signatureAlgorithm);
        }

        if (!empty($this->stubPath)) {
            $phar->setStub(file_get_contents($this->stubPath));
        } else {
            if (!empty($this->cliStubFile)) {
                $cliStubFile = $this->cliStubFile->getPathWithoutBase($this->baseDirectory);
            } else {
                $cliStubFile = null;
            }

            if (!empty($this->webStubFile)) {
                $webStubFile = $this->webStubFile->getPathWithoutBase($this->baseDirectory);
            } else {
                $webStubFile = null;
            }

            $phar->setDefaultStub($cliStubFile, $webStubFile);
        }

        if ($this->metadata === null) {
            $this->createMetaData();
        }

        if ($metadata = $this->metadata->toArray()) {
            $phar->setMetadata($metadata);
        }

        if (!empty($this->alias)) {
            $phar->setAlias($this->alias);
        }

        return $phar;
    }

    /**
     * @return Phar
     */
    private function initPhar()
    {
        /**
         * Delete old package, if exists.
         */
        if ($this->destinationFile->exists()) {
            $this->destinationFile->delete();
        }
        $phar = $this->buildPhar();

        return $phar;
    }

    /**
     * @param Phar   $phar
     * @param string $baseDirectory
     */
    private function compressEachFile(Phar $phar, $baseDirectory)
    {
        $phar->startBuffering();

        foreach ($this->filesets as $fileset) {
            $this->log(
                'Adding specified files in ' . $fileset->getDir($this->project) . ' to package',
                Project::MSG_VERBOSE
            );

            if (Phar::NONE != $this->compression) {
                foreach ($fileset as $file) {
                    $localName = substr($file, strlen($baseDirectory) + 1);
                    $this->log($localName . "... ", Project::MSG_VERBOSE);
                    $phar->addFile($file, $localName);
                    $phar[$localName]->compress($this->compression);
                }
            } else {
                $phar->buildFromIterator($fileset, $baseDirectory);
            }
        }

        $phar->stopBuffering();
    }

    /**
     * @param Phar   $phar
     * @param string $baseDirectory
     */
    private function compressAllFiles(Phar $phar, $baseDirectory)
    {
        $total = 0;

        $phar->startBuffering();

        foreach ($this->filesets as $fileset) {
            $dir = $fileset->getDir($this->project);
            $msg = sprintf("Fileset %s ...", $dir);
            $this->log($msg, Project::MSG_VERBOSE);
            $added = $phar->buildFromIterator($fileset, $baseDirectory);
            $total += count($added);
        }

        $phar->stopBuffering();

        if (Phar::NONE === $this->compression) {
            return;
        }

        $msg = sprintf("Compressing %d files (compression: %s) ... ", $total, $this->getCompressionLabel());
        $this->log($msg, Project::MSG_VERBOSE);

        // safeguard open files soft limit
        if (function_exists('posix_getrlimit')) {
            $rlimit = posix_getrlimit();
            if ($rlimit['soft openfiles'] < ($total + 5)) {
                $msg = sprintf("Limit of openfiles (%d) is too low.", $rlimit['soft openfiles']);
                $this->log($msg, Project::MSG_VERBOSE);
            }
        }

        // safeguard compression
        try {
            $phar->compressFiles($this->compression);
        } catch (BadMethodCallException $e) {
            if ($e->getMessage() === 'unable to create temporary file') {
                $msg = sprintf("Info: Check openfiles limit it must be %d or higher", $total + 5);
                throw new BadMethodCallException($msg, 0, $e);
            }
            throw $e;
        }
    }
}
