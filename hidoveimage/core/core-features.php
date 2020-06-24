<?php
namespace hidoveimage\core;

class plugin_features
{
    /**
     * get_filemtime
     *
     * @params string $file_name
     * @params string date format
     * @return string file date
     * @version 1.2
     */
    public static function get_filemtime($file_name = null, $format = 'YmdHis')
    {
        if (!$file_name || !file_exists($file_name) || !is_file($file_name)) {
            return false;
        }

        $file_date = filemtime($file_name);
        $file_date = date($format, $file_date);
        return $file_date;
    }
    /**
     * get_plugin_file_path
     *
     * @params string file name
     * @return string file path
     * @version 1.0.0
     */
    public static function get_plugin_file_path($file_name = null)
    {
        $base_path = dirname(plugin_dir_path(__FILE__));
        $file_path = $base_path . '/' . $file_name;
        return $file_path;
    }
    /**
     * get_plugin_file_url
     *
     * @params string file name
     * @params bool true return include the file modiftime
     * @return string file url
     * @version 1.0.0
     */
    public static function get_plugin_file_url($file_name = null, $mtime = true)
    {
        $base_url = dirname(plugin_dir_url(__FILE__));
        $file_path = self::get_plugin_file_path($file_name);
        $file_url = $file_name ? $base_url . '/' . $file_name : $base_url;
        if (file_exists($file_path) && is_file($file_path)) {
            if ($mtime) {
                $file_time = self::get_filemtime($file_path);
                $file_url = $file_url . '?t=' . $file_time;
            }
        }
        return $file_url;
    }
    /**
     * get_plugin_js
     *
     * @params string file name
     * @params bool true return the js file url only, does not include <script> tag
     * @params bool true return include the file modiftime
     * @return string
     * @version 1.0.1
     */
    public static function get_plugin_js($file_name = null, $url_only = true, $mtime = true)
    {
        $basedir_name = 'static/js/';
        $base_ext = '.js';
        if (!$file_name) {
            $output = self::get_plugin_file_url() . '/' . $basedir_name;
            return $output;
        }

        $file_info = pathinfo($file_name);
        $file_name = $file_name . $base_ext;
        $file_path_name = $basedir_name . $file_name;

        $file_http_path = self::get_plugin_file_url($file_path_name, $mtime);

        if ($url_only == true) {
            $output = $file_http_path;
        } else {
            $output = '<script src="' . $file_http_path . '"></script>';
        }
        return $output;
    }

    /**
     * get_plugin_css
     *
     * @params string file name
     * @params array return other <link> tag attr
     * @params bool true return include the file modiftime
     * @return string
     * @version 1.0.0
     */
    public static function get_plugin_css($file_name = null, $param = null, $mtime = true)
    {
        $basedir_name = 'static/css/';
        $base_ext = '.css';
        if (!$file_name) {
            $output = self::get_plugin_file_url() . '/' . $basedir_name;
            return $output;
        }

        $file_info = pathinfo($file_name);
        $file_name = isset($file_info['extension']) ? $file_name : $file_name . $base_ext;
        $file_path_name = $basedir_name . $file_name;

        $file_http_path = self::get_plugin_file_url($file_path_name, $mtime);

        if ($param === 'normal') {
            $output = '<link href="' . $file_http_path . '" rel="stylesheet" media="all"/>';
        } else if (is_array($param)) {
            /* check the array of $param to release */
            $ext = null;
            foreach ($param as $key => $value) {
                $ext .= ' ' . $key . '="' . $value . '"';
            }
            $output = '<link href="' . $file_http_path . '" rel="stylesheet" ' . $ext . '/>';
        } else {
            $output = $file_http_path;
        }
        return $output;
    }
    /**
     * get the process file url of theme
     *
     * @param array $param The url args
     * @return string The process file url
     * @version 1.2.0
     *
     */
    public static function get_process_url(array $param = array())
    {
        static $admin_ajax_url = null;

        if ($admin_ajax_url === null) {
            $admin_ajax_url = admin_url('admin-ajax.php');
        }

        if (!$param) {
            return $admin_ajax_url;
        }

        return $admin_ajax_url . '?' . http_build_query($param);
    }
}
