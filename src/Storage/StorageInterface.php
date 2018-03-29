<?php
declare(strict_types=1);

namespace C3\PhpStorage\Storage;

use C3\PhpStorage\Model\ChangedFiles;

interface StorageInterface
{
    const FILE_TYPE_DIR = 'dir';
    const FILE_TYPE_FILE = 'file';

    public function syncRemoteToLocal(string $remotePath, \SplFileInfo $localPath, bool $delete = false): ChangedFiles;

    public function syncRemoteToLocalParallel(
        string $remotePath,
        \SplFileInfo $localPath,
        bool $delete = false
    ): ChangedFiles;

    public function download(string $filePath, string $destinationPath): void;

    public function downloadContent(string $filePath): string;
}
