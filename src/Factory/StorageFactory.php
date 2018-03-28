<?php
declare(strict_types=1);

namespace C3\PhpStorage\Factory;

use C3\PhpStorage\Exception\FileSystem\UnknownFileSystemTypeException;
use C3\PhpStorage\Storage\Storage;
use C3\PhpStorage\Storage\StorageInterface;
use C3\PhpStorage\Type\StorageTypeEnum;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use League\Flysystem\WebDAV\WebDAVAdapter;
use Sabre\DAV\Client;

class StorageFactory
{
    /**
     * @param \C3\PhpStorage\Type\StorageTypeEnum $type
     * @param string $baseUri
     * @param array $settings
     * @return \C3\PhpStorage\Storage\StorageInterface
     * @throws \C3\PhpStorage\Exception\FileSystem\UnknownFileSystemTypeException
     * @throws \InvalidArgumentException
     * @throws \LogicException
     */
    public static function create(
        StorageTypeEnum $type,
        string $baseUri,
        array $settings
    ): StorageInterface {
        switch ($type) {
            case StorageTypeEnum::LOCAL():
                $adapter = new Local($baseUri);
                break;
            case StorageTypeEnum::WEB_DAV():
                $settings['baseUri'] = $baseUri;
                $client = new Client($settings);
                $adapter = new WebDAVAdapter($client);
                break;
            default:
                throw new UnknownFileSystemTypeException(
                    "Couldn't find an file system adapter for TYPE " . $type
                );
        }

        return new Storage(new Filesystem($adapter));
    }
}
