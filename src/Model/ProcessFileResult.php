<?php
declare(strict_types=1);

namespace C3\PhpStorage\Model;

class ProcessFileResult
{
    /**
     * @var ProcessFileStatusEnum
     */
    protected $status;

    /**
     * @var \C3\PhpStorage\Model\ProcessFileChangeTypeEnum
     */
    protected $changeType;

    /**
     * @var \SplFileInfo
     */
    protected $file;

    /**
     * @var array
     */
    protected $data;

    /**
     * @return \C3\PhpStorage\Model\ProcessFileStatusEnum
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param \C3\PhpStorage\Model\ProcessFileStatusEnum $status
     * @return $this
     */
    public function setStatus(\C3\PhpStorage\Model\ProcessFileStatusEnum $status)
    {
        $this->status = $status;
        return $this;
    }

    /**
     * @return \C3\PhpStorage\Model\ProcessFileChangeTypeEnum
     */
    public function getChangeType()
    {
        return $this->changeType;
    }

    /**
     * @param \C3\PhpStorage\Model\ProcessFileChangeTypeEnum $changeType
     * @return $this
     */
    public function setChangeType(\C3\PhpStorage\Model\ProcessFileChangeTypeEnum $changeType)
    {
        $this->changeType = $changeType;
        return $this;
    }


    /**
     * @return \SplFileInfo
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * @param \SplFileInfo $file
     * @return $this
     */
    public function setFile(\SplFileInfo $file)
    {
        $this->file = $file;
        return $this;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param array $data
     * @return $this
     */
    public function setData(array $data)
    {
        $this->data = $data;
        return $this;
    }
}
