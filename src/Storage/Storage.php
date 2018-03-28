<?php
declare(strict_types=1);

namespace C3\PhpStorage\Storage;

use C3\PhpStorage\Exception\FileNotReadableException;
use C3\PhpStorage\Utility\FileUtility;
use C3\Storage\Model\ChangedFiles;
use C3\Storage\Storage\StorageInterface;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\Filesystem;

class Storage implements StorageInterface
{
    protected $fileSystem;

    public function __construct(Filesystem $fileSystem)
    {
        $this->fileSystem = $fileSystem;
    }

    /**
     * @param string $remotePath
     * @param \SplFileInfo $localPath
     * @param bool $delete
     * @return \C3\Storage\Model\ChangedFiles
     * @throws \League\Flysystem\FileNotFoundException
     */
    public function syncRemoteToLocal(string $remotePath, \SplFileInfo $localPath, bool $delete = false): ChangedFiles
    {
        $changedFiles = new ChangedFiles();
        $localPath = FileUtility::addTrailingSlash($localPath->getRealPath());
        $remotePath = FileUtility::addTrailingSlash($remotePath);

        $remoteFiles = $this->fileSystem->listContents($remotePath, true);
        foreach ($remoteFiles as $remoteFile) {
            $pathWithoutPrefix = FileUtility::removeBasePath($remoteFile['path'], $remotePath);
            $localFilePath = $localPath . $pathWithoutPrefix;
            $localFile = new \SplFileInfo($localFilePath);

            if (!file_exists($localFilePath)) {
                // File doesn't exist in $localPath
                if ($remoteFile['type'] === StorageInterface::FILE_TYPE_DIR) {
                    mkdir($localFilePath . $pathWithoutPrefix, 0700, true);
                } else {
                    $this->download($remoteFile['path'], $localFilePath);
                    $changedFiles->addAddedFile(new \SplFileInfo($localFilePath));
                }
            } elseif ($this->hasChanged($remoteFile, $localFile)) {
                // Changed file? Directory are ignored
                $this->download($remoteFile['path'], $localFile->getRealPath());
                $changedFiles->addChangedFile($localFile);
            }
        }

        // Check for deleted files (files which exist in $localPath, but not in $remotePath)
        $localFiles = FileUtility::getAllFilesAndFoldersInPath([], $localPath);
        foreach ($localFiles as $localFile) {
            $localFileWithoutPrefix = FileUtility::removeBasePath($localFile, $localPath);
            if (!$this->fileSystem->has($remotePath . $localFileWithoutPrefix)) {
                if ($delete) {
                    // Delete file, if the delete flag was set. Otherwise the file is only added to removedFiles
                    unlink($localFile);
                }
                $changedFiles->addRemovedFile(new \SplFileInfo($localFile));
            }
        }
        return $changedFiles;
    }

    protected function hasChanged(array $remoteFile, \SplFileInfo $localFile): bool
    {
        if ($remoteFile['type'] !== StorageInterface::FILE_TYPE_DIR) {
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
