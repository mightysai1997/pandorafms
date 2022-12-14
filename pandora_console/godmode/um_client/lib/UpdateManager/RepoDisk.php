<?php
require_once __DIR__.'/Repo.php';

/**
 * Disk repository abstraction layer.
 */
class RepoDisk extends Repo
{


    /**
     * Class Constructor.
     *
     * @param $path Path of the package repository.
     */
    function __construct($path=false, $extension='oum')
    {
        // Check the repository can be opened.
        if (($dh = opendir($path)) === false) {
            throw new Exception('error opening repository '.$path);
        }

        closedir($dh);

        parent::__construct($path);

        $this->extension = $extension;
    }


    /**
     * Delete a directory and its contents recursively
     */
    public static function delete_dir($dirname)
    {
        if (is_dir($dirname)) {
            $dir_handle = @opendir($dirname);
        }

        if (!$dir_handle) {
            return false;
        }

        while ($file = readdir($dir_handle)) {
            if ($file != '.' && $file != '..') {
                if (!is_dir($dirname.'/'.$file)) {
                    @unlink($dirname.'/'.$file);
                } else {
                    self::delete_dir($dirname.'/'.$file);
                }
            }
        }

        closedir($dir_handle);
        @rmdir($dirname);

        return true;
    }


    /**
     * Load repository files.
     */
    protected function load()
    {
        if ($this->files !== false) {
            return;
        }

        // Read files in the repository.
        if (($dh = opendir($this->path)) === false) {
            throw new Exception('error opening repository');
        }

        $this->files = [];
        while ($file_name = readdir($dh)) {
            // Files must contain a version number.
            if (preg_match('/([\d\.]+?)\_x86_64.'.$this->extension.'$/', $file_name, $utimestamp) === 1
                || preg_match('/([\d\.]+?)\.'.$this->extension.'$/', $file_name, $utimestamp) === 1
            ) {
                // Add the file to the repository.
                $this->files[$utimestamp[1]] = $file_name;
            }
        }

        closedir($dh);

        // Sort them according to the package UNIX timestamp.
        krsort($this->files);
    }


    /**
     * Reload repository files.
     */
    public function reload()
    {
        $this->files = false;
        $this->load();
    }


}
