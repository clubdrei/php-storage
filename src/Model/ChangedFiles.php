<?php
declare(strict_types=1);

namespace C3\PhpStorage\Model;

use SplFileInfo;

class ChangedFiles
{
    /**
     * @var \SplFileInfo[]
     */
    protected $removedFiles = [];

    /**
     * @var \SplFileInfo[]
     */
    protected $changedFiles = [];

    /**
     * @var \SplFileInfo[]
     */
    protected $addedFiles = [];

    /**
     * @return \SplFileInfo[]
     */
    public function getRemovedFiles()
    {
        return $this->removedFiles;
    }

    /**
     * @param \SplFileInfo[] $removedFiles
     * @return $this
     */
    public function setRemovedFiles(array $removedFiles)
    {
        $this->removedFiles = $removedFiles;
        return $this;
    }

    public function addRemovedFile(SplFileInfo $file)
    {
        $this->removedFiles[] = $file;
        return $this;
    }

    /**
     * @return \SplFileInfo[]
     */
    public function getChangedFiles()
    {
        return $this->changedFiles;
    }

    /**
     * @param \SplFileInfo[] $changedFiles
     * @return $this
     */
    public function setChangedFiles(array $changedFiles)
    {
        $this->changedFiles = $changedFiles;
        return $this;
    }

    public function addChangedFile(SplFileInfo $file)
    {
        $this->changedFiles[] = $file;
        return $this;
    }

    /**
     * @return \SplFileInfo[]
     */
    public function getAddedFiles()
    {
        return $this->addedFiles;
    }

    /**
     * @param \SplFileInfo[] $addedFiles
     * @return $this
     */
    public function setAddedFiles(array $addedFiles)
    {
        $this->addedFiles = $addedFiles;
        return $this;
    }

    public function addAddedFile(SplFileInfo $file)
    {
        $this->addedFiles[] = $file;
        return $this;
    }

    public function hasChanges(): bool
    {
        return !empty($this->removedFiles) || !empty($this->changedFiles) || !empty($this->addedFiles);
    }
}
