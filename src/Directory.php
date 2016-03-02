<?php
namespace Shin1x1\EphemeralFilesystem;

use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

class Directory
{
    const RETRY_MAX_CREATE_DIRECTORY = 10;
    const DIRECTORY_NAME_LENGTH = 8;

    /**
     * @var string
     */
    private $directoryBasePath = '';

    /**
     * @var string
     */
    private $directoryPath = '';

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * TemporaryFilesystem constructor.
     * @param string $directoryBasePath
     * @param Filesystem $filesystem
     */
    public function __construct($directoryBasePath, Filesystem $filesystem = null)
    {
        $this->directoryBasePath = $directoryBasePath ?: sys_get_temp_dir();
        $this->filesystem = $filesystem ?: new Filesystem();
    }

    /**
     * @param callable $callback
     * @return string
     */
    public function using(callable $callback)
    {
        $this->directoryPath = $this->createDirectory($this->directoryBasePath);

        try {
            return call_user_func($callback, $this->directoryPath);
        } finally {
            $this->dispose();
        }
    }

    /**
     * @param callable $callback
     * @return string
     */
    public function usingUntilTheEndOfTheRequest(callable $callback)
    {
        $this->directoryPath = $this->createDirectory($this->directoryBasePath);

        // register discard callback
        register_shutdown_function(function () {
            $this->dispose();
        });

        return call_user_func($callback, $this->directoryPath);
    }

    /**
     * @param string $outputBaseDirectory
     * @return string
     * @throws \Exception
     */
    private function createDirectory($outputBaseDirectory)
    {
        if (!$this->filesystem->exists($outputBaseDirectory)) {
            $this->filesystem->mkdir($outputBaseDirectory);
        }

        $outputDirectory = '';
        for ($i = 1; $i <= static::RETRY_MAX_CREATE_DIRECTORY; $i++) {
            $directory = bin2hex(openssl_random_pseudo_bytes(static::DIRECTORY_NAME_LENGTH));
            if (!$this->filesystem->exists($outputBaseDirectory . DIRECTORY_SEPARATOR . $directory)) {
                $outputDirectory = $outputBaseDirectory . DIRECTORY_SEPARATOR . $directory;
                break;
            }
        }

        if (empty($outputDirectory)) {
            $message = sprintf('Output directory can not created. [%s]', $outputBaseDirectory);
            throw new IOException($message);
        }

        $this->filesystem->mkdir($outputDirectory, 0700);

        return $outputDirectory;
    }

    /**
     *
     */
    public function dispose()
    {
        if ($this->directoryPath) {
            $this->filesystem->remove($this->directoryPath);
            $this->directoryPath = '';
        }
    }
}
