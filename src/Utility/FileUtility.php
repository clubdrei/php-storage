<?php
namespace C3\PhpStorage\Utility;

class FileUtility
{
    public static function addTrailingSlash(string $path): string
    {
        $path = rtrim($path, '/\\');
        return $path . DIRECTORY_SEPARATOR;
    }


    /**
     * @param array $files
     * @param string $basePath
     * @return array
     */
    public static function removeBasePathFromFileArray(array $files, string $basePath): array
    {
        $dir1Length = strlen($basePath);
        foreach ($files as $key => $filePath) {
            $newFilePath = ltrim(substr($filePath, $dir1Length), '/');
            if (!empty($newFilePath)) {
                $files[md5($newFilePath)] = $newFilePath;
            }
            unset($files[$key]);
        }

        return $files;
    }

    public static function removeBasePath(string $filePath, $basePath): string
    {
        $filePath = trim($filePath, '/');
        $prefix = trim($basePath, '/');

        return ltrim(substr($filePath, strlen($prefix)), '/');
    }

    /**
     * Recursively gather all files and folders of a path.
     *
     * @param array $fileArr Empty input array (will have files added to it)
     * @param string $path The path to read recursively from (absolute) (include trailing slash!)
     * @param string $extList Comma list of file extensions: Only files with extensions in this list (if applicable) will be selected.
     * @param bool $regDirs If set, directories are also included in output.
     * @param int $recursivityLevels The number of levels to dig down...
     * @param string $excludePattern regex pattern of files/directories to exclude
     * @return array An array with the found files/directories.
     */
    public static function getAllFilesAndFoldersInPath(
        array $fileArr,
        $path,
        $extList = '',
        $regDirs = false,
        $recursivityLevels = 99,
        $excludePattern = ''
    ) {
        if ($regDirs) {
            $fileArr[md5($path)] = $path;
        }
        $fileArr = array_merge($fileArr, self::getFilesInDir($path, $extList, 1, 1, $excludePattern));
        $dirs = self::get_dirs($path);
        if ($recursivityLevels > 0 && is_array($dirs)) {
            foreach ($dirs as $subdirs) {
                if ((string)$subdirs !== '' && ($excludePattern === '' || !preg_match(
                            ('/^' . $excludePattern . '$/'),
                            $subdirs
                        ))) {
                    $fileArr = self::getAllFilesAndFoldersInPath(
                        $fileArr,
                        $path . $subdirs . '/',
                        $extList,
                        $regDirs,
                        $recursivityLevels - 1,
                        $excludePattern
                    );
                }
            }
        }
        return $fileArr;
    }

    /**
     * Finds all files in a given path and returns them as an array. Each
     * array key is a md5 hash of the full path to the file. This is done because
     * 'some' extensions like the import/export extension depend on this.
     *
     * @param string $path The path to retrieve the files from.
     * @param string $extensionList A comma-separated list of file extensions. Only files of the specified types will be retrieved. When left blank, files of any type will be retrieved.
     * @param bool $prependPath If TRUE, the full path to the file is returned. If FALSE only the file name is returned.
     * @param string $order The sorting order. The default sorting order is alphabetical. Setting $order to 'mtime' will sort the files by modification time.
     * @param string $excludePattern A regular expression pattern of file names to exclude. For example: 'clear.gif' or '(clear.gif|.htaccess)'. The pattern will be wrapped with: '/^' and '$/'.
     * @return array|string Array of the files found, or an error message in case the path could not be opened.
     */
    public static function getFilesInDir(
        $path,
        $extensionList = '',
        $prependPath = false,
        $order = '',
        $excludePattern = ''
    ) {
        $excludePattern = (string)$excludePattern;
        $path = rtrim($path, '/');
        if (!@is_dir($path)) {
            return [];
        }

        $rawFileList = scandir($path);
        if ($rawFileList === false) {
            return 'error opening path: "' . $path . '"';
        }

        $pathPrefix = $path . '/';
        $allowedFileExtensionArray = self::trimExplode(',', $extensionList);
        $extensionList = ',' . str_replace(' ', '', $extensionList) . ',';
        $files = [];
        foreach ($rawFileList as $entry) {
            $completePathToEntry = $pathPrefix . $entry;
            if (!@is_file($completePathToEntry)) {
                continue;
            }

            foreach ($allowedFileExtensionArray as $allowedFileExtension) {
                if (
                    ($extensionList === ',,' || stripos(
                            $extensionList,
                            ',' . substr(
                                $entry,
                                strlen($allowedFileExtension) * -1,
                                strlen($allowedFileExtension)
                            ) . ','
                        ) !== false)
                    && ($excludePattern === '' || !preg_match(('/^' . $excludePattern . '$/'), $entry))
                ) {
                    if ($order !== 'mtime') {
                        $files[] = $entry;
                    } else {
                        // Store the value in the key so we can do a fast asort later.
                        $files[$entry] = filemtime($completePathToEntry);
                    }
                }
            }
        }

        $valueName = 'value';
        if ($order === 'mtime') {
            asort($files);
            $valueName = 'key';
        }

        $valuePathPrefix = $prependPath ? $pathPrefix : '';
        $foundFiles = [];
        foreach ($files as $key => $value) {
            // Don't change this ever - extensions may depend on the fact that the hash is an md5 of the path! (import/export extension)
            $foundFiles[md5($pathPrefix . ${$valueName})] = $valuePathPrefix . ${$valueName};
        }

        return $foundFiles;
    }

    /**
     * Returns an array with the names of folders in a specific path
     * Will return 'error' (string) if there were an error with reading directory content.
     *
     * @param string $path Path to list directories from
     * @return array Returns an array with the directory entries as values. If no path, the return value is nothing.
     */
    public static function get_dirs($path)
    {
        $dirs = null;
        if ($path) {
            if (is_dir($path)) {
                $dir = scandir($path);
                $dirs = [];
                foreach ($dir as $entry) {
                    if (is_dir($path . '/' . $entry) && $entry !== '..' && $entry !== '.') {
                        $dirs[] = $entry;
                    }
                }
            } else {
                $dirs = 'error';
            }
        }
        return $dirs;
    }

    /**
     * Explodes a string and trims all values for whitespace in the end.
     * If $onlyNonEmptyValues is set, then all blank ('') values are removed.
     *
     * @param string $delim Delimiter string to explode with
     * @param string $string The string to explode
     * @param bool $removeEmptyValues If set, all empty values will be removed in output
     * @param int $limit If limit is set and positive, the returned array will contain a maximum of limit elements with
     *                   the last element containing the rest of string. If the limit parameter is negative, all components
     *                   except the last -limit are returned.
     * @return array Exploded values
     */
    public static function trimExplode($delim, $string, $removeEmptyValues = false, $limit = 0)
    {
        $result = explode($delim, $string);
        if ($removeEmptyValues) {
            $temp = [];
            foreach ($result as $value) {
                if (trim($value) !== '') {
                    $temp[] = $value;
                }
            }
            $result = $temp;
        }
        if ($limit > 0 && count($result) > $limit) {
            $lastElements = array_splice($result, $limit - 1);
            $result[] = implode($delim, $lastElements);
        } elseif ($limit < 0) {
            $result = array_slice($result, 0, $limit);
        }
        $result = array_map('trim', $result);
        return $result;
    }
}
