# PHP Storage
## Usage
### Create storage object
```php
$storage = StorageFactory::create(
    StorageTypeEnum::WEB_DAV(),
    'https://webdav.example.com',
    [
        'prefix' => 'basePathToWebDAV/',
        'userName' => 'user',
        'password' => 'password',
    ]
);
```

### Stream remote file to local file system
```php
$this->storage->download('remotePathInsideWebDAV/test.txt', 'localPath/test.txt');
```

### Get remote file content
```php
$content = $this->storage->downloadContent('remotePathInsideWebDAV/test.txt');
```

### Sync remote folder structure to local filesystem (recursive)
```php
$localFile = new \SplFileInfo('localPath/');
storage->syncRemoteToLocal('remotePathInsideWebDAVRoot/', $localFile,true);
```

# Credits
* [Flysystem](https://flysystem.thephpleague.com/)
* [Flysystem WebDAV](https://github.com/thephpleague/flysystem-webdav)
* [MyCLabs PHP Enum](https://github.com/myclabs/php-enum)