<?php

namespace Unisharp\Laravelfilemanager\traits;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Input;

trait LfmHelpers
{
    protected $url_location = null;
    protected $dir_location = null;

    public function __construct()
    {
        if (!$this->isProcessingImages() && !$this->isProcessingFiles()) {
            throw new \Exception('unexpected type parameter');
        }

        $this->url_location = Config::get('lfm.' . $this->currentLfmType() . 's_url');
        $this->dir_location = Config::get('lfm.' . $this->currentLfmType() . 's_dir');
    }

    /*****************************
     ***   Private Functions   ***
     *****************************/
    private function formatLocation($location, $type = null, $get_thumb = false)
    {
        if ($type === 'share') {
            return $location . Config::get('lfm.shared_folder_name');
        } elseif ($type === 'user') {
            return $location . $this->getUserSlug();
        }

        $working_dir = Input::get('working_dir');

        // remove first slash
        if (substr($working_dir, 0, 1) === '/') {
            $working_dir = substr($working_dir, 1);
        }

        $location .= $working_dir;

        if ($type === 'directory' || $type === 'thumb') {
            $location .= '/';
        }

        //if user is inside thumbs folder there is no need
        // to add thumbs substring to the end of $location
        $in_thumb_folder = preg_match('/'.Config::get('lfm.thumb_folder_name').'$/i', $working_dir);

        if ($type === 'thumb' && !$in_thumb_folder) {
            $location .= Config::get('lfm.thumb_folder_name') . '/';
        }

        return $location;
    }


    /****************************
     ***   Shared Functions   ***
     ****************************/


    public function getUserSlug()
    {
        $slug_of_user = \Config::get('lfm.user_field');

        return empty(auth()->user()) ? '' : auth()->user()->$slug_of_user;
    }


    public function getPath($type = null, $get_thumb = false)
    {
        $path = base_path() . '/' . $this->dir_location;

        $path = $this->formatLocation($path, $type);

        return $path;
    }


    public function getUrl($type = null)
    {
        $url = $this->url_location;

        $url = $this->formatLocation($url, $type);

        $url = str_replace('\\', '/', $url);

        return $url;
    }


    public function getDirectories($path)
    {
        $thumb_folder_name = Config::get('lfm.thumb_folder_name');
        $all_directories = File::directories($path);

        $arr_dir = [];

        foreach ($all_directories as $directory) {
            $dir_name = $this->getFileName($directory);

            if ($dir_name['short'] !== $thumb_folder_name) {
                $arr_dir[] = $dir_name;
            }
        }

        return $arr_dir;
    }


    public function getFileName($file)
    {
        $lfm_dir_start = strpos($file, $this->dir_location);
        $working_dir_start = $lfm_dir_start + strlen($this->dir_location);
        $lfm_file_path = substr($file, $working_dir_start);

        $arr_dir = explode('/', $lfm_file_path);
        $arr_filename['short'] = end($arr_dir);
        $arr_filename['long'] = '/' . $lfm_file_path;

        return $arr_filename;
    }

    public function isProcessingImages()
    {
        return $this->currentLfmType() === 'image';
    }

    public function isProcessingFiles()
    {
        return $this->currentLfmType() === 'file';
    }

    public function currentLfmType($is_for_url = false)
    {
        $file_type = Input::get('type', 'Images');

        if ($is_for_url) {
            return ucfirst($file_type);
        } else {
            return lcfirst(str_singular($file_type));
        }
    }

    public function allowMultiUser()
    {
        return \Config::get('lfm.allow_multi_user') === true;
    }

    public function createFolderByPath($path)
    {
        File::makeDirectory($path, $mode = 0777, true, true);
    }

    public function error($error_type, $variables = [])
    {
        return \Lang::get('laravel-filemanager::lfm.error-' . $error_type, $variables);
    }

    public function rootFolder($type)
    {
        $folder_path = '/';

        if ($type === 'user') {
            $folder_path .= $this->getUserSlug();
        } else {
            $folder_path .= config('lfm.shared_folder_name');
        }

        return $folder_path;
    }
}
