<?php
declare(strict_types=1);

namespace C3\PhpStorage\Storage;

use C3\PhpStorage\Exception\FileNotReadableException;
use C3\PhpStorage\Model\ProcessFileChangeTypeEnum;
use C3\PhpStorage\Model\ProcessFileResult;
use C3\PhpStorage\Model\ProcessFileStatusEnum;
use C3\PhpStorage\Utility\FileUtility;
use C3\PhpStorage\Model\ChangedFiles;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\Filesystem;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class Storage implements StorageInterface
{
    /**
     * @var \League\Flysystem\Filesystem
     */
    protected $fileSystem;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(Filesystem $fileSystem, LoggerInterface $logger = null)
    {
        $this->fileSystem = $fileSystem;
        if (null === $logger) {
            $this->logger = new NullLogger();
        } else {
            $this->logger = $logger;
        }
    }

    /**
     * @param string $remotePath
     * @param \SplFileInfo $localPath
     * @param bool $delete
     * @return \C3\PhpStorage\Model\ChangedFiles
     */
    public function syncRemoteToLocal(string $remotePath, \SplFileInfo $localPath, bool $delete = false): ChangedFiles
    {
        $changedFiles = new ChangedFiles();
        $localPath = FileUtility::addTrailingSlash($localPath->getRealPath());
        $remotePath = FileUtility::addTrailingSlash($remotePath);

        $remoteFiles = $this->fileSystem->listContents($remotePath, true);
        foreach ($remoteFiles as $remoteFile) {
            $changedFiles->addProcessFileResult($this->processRemoteFile($remoteFile, $remotePath, $localPath));
        }

        $changedFiles->setRemovedFiles($this->findRemovedFiles(new \SplFileInfo($localPath), $remotePath, $delete));

        return $changedFiles;
    }

    protected function findRemovedFiles(\SplFileInfo $localPath, string $remotePath, bool $delete): array
    {
        $files = [];

        // Check for deleted files (files which exist in $localPath, but not in $remotePath)
        $localFiles = FileUtility::getAllFilesAndFoldersInPath([], $localPath);
        foreach ($localFiles as $localFile) {
            $localFileWithoutPrefix = FileUtility::removeBasePath($localFile, $localPath);
            if (!$this->fileSystem->has($remotePath . $localFileWithoutPrefix)) {
                if ($delete) {
                    // Delete file, if the delete flag was set. Otherwise the file is only added to removedFiles
                    unlink($localFile);
                }
                $files[] = new \SplFileInfo($localFile);
            }
        }

        return $files;
    }

    /**
     * @param array $remoteFile
     * @param string $remotePath
     * @param string $localPath
     * @return \C3\PhpStorage\Model\ProcessFileResult
     */
    protected function processRemoteFile(array $remoteFile, string $remotePath, string $localPath): ProcessFileResult
    {
        $result = new ProcessFileResult();
        $result->setStatus(ProcessFileStatusEnum::OK());

        $this->logger->info('Start processing remote file', $remoteFile);

        try {
            $pathWithoutPrefix = FileUtility::removeBasePath($remoteFile['path'], $remotePath);
            $localFilePath = $localPath . $pathWithoutPrefix;
            $localFile = new \SplFileInfo($localFilePath);

            $result->setFile($localFile);

            if (!file_exists($localFilePath)) {
                // File doesn't exist in $localPath
                if ($remoteFile['type'] === StorageInterface::FILE_TYPE_DIR) {
                    if (!file_exists($localFilePath)) {
                        mkdir($localFilePath, 0700, true);
                    }
                } else {
                    $this->download($remoteFile['path'], $localFilePath);
                    $result->setChangeType(ProcessFileChangeTypeEnum::ADD());
                }
            } elseif ($this->hasChanged($remoteFile, $localFile)) {
                // Changed file? Directory are ignored
                $this->download($remoteFile['path'], $localFile->getRealPath());
                $result->setChangeType(ProcessFileChangeTypeEnum::CHANGE());
            }
        } catch (\Throwable $e) {
            $result->setStatus(ProcessFileStatusEnum::ERROR());
            $result->setData(['error' => $e->getMessage(), 'stackTrace' => $e->getTraceAsString()]);
        }

        $this->logger->info('Finished processing remote file', ['result' => var_export($result, true)]);

        return $result;
    }

    protected function hasChanged(array $remoteFile, \SplFileInfo $localFile): bool
    {
        if ($remoteFile['type'] !== StorageInterface::FILE_TYPE_FILE) {
            // Ignore directories and symlinks
            return false;
        }

        if ($localFile->getSize() !== (int)$remoteFile['size']) {
            // Different size
            return true;
        }

        if ((int)$remoteFile['timestamp'] > $localFile->getMTime()) {
            // Remote file is newer
            return true;
        }

        return false;
    }

    /**
     * @param string $filePath
     * @param string $destinationPath
     * @throws \League\Flysystem\FileNotFoundException
     */
    public function download(string $filePath, string $destinationPath): void
    {
        $stream = $this->fileSystem->readStream($filePath);
        file_put_contents($destinationPath, $stream);
        // Preserve mtime
        $timestamp = $this->fileSystem->getTimestamp($filePath);
        if (!empty($timestamp) && is_numeric($timestamp)) {
            touch($destinationPath, $timestamp);
        }
        if (is_resource($stream)) {
            fclose($stream);
        }
    }

    /**
     * @param string $filePath
     * @return string
     * @throws \C3\PhpStorage\Exception\FileNotReadableException
     * @throws \C3\PhpStorage\Exception\FileNotFoundException
     */
    public function downloadContent(string $filePath): string
    {
        try {
            $content = $this->fileSystem->read($filePath);
        } catch (FileNotFoundException $e) {
            throw new \C3\PhpStorage\Exception\FileNotFoundException($e->getMessage());
        }

        if (!is_string($content)) {
            throw new FileNotReadableException("Couldn't read file on server. Path: " . $filePath);
        }
        return $content;
    }
}
