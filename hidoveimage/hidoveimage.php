<?php
/*
Plugin Name: Hidove图床上传插件
Plugin URI: https://blog.hidove.cn
Description: 免费公共图床, 提供图片上传和图片外链服务, 原图保存, 隐私相册, 全球CDN加速.
Author: Ivey
Author URI: https://blog.hidove.cn
Version: 1.0
 */
add_action('plugins_loaded', 'hidoveimage::init');

//register_activation_hook(__FILE__, 'hidoveimage::init');

class hidoveimage
{

    public static $iden = 'hidoveimage';

    private static $plugin_features = 'hidoveimage\\core\\plugin_features';
    private static $plugin_functions = 'hidoveimage\\core\\plugin_functions';
    private static $plugin_options = 'hidoveimage\\core\\plugin_options';

    private static $plugin_data;

    private static $basedir_backup = '/hidoveimage-backup/';
    private static $thumbnail_external_key = 'thumbnail-external-url';
    private static $allow_types = array('png', 'gif', 'jpg', 'jpeg');
    private static $file_url = null;
    private static $key_authorization = 'authorization';
    private static $b = array('a', 'b', 'c', 'd', 'e');
    private static $p = array('_');
    private static $d = array('1', '2', '3', '4', '5', '6');
    private static $q = array('s', 'o');
    private static $wb = array();
    private static $available_times = 10;
    private static $cache_backup_files = array();
    private static function tdomain()
    {
        load_plugin_textdomain(__CLASS__, false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }
    private static function get_header_translate($key = null)
    {
        $trs = array(
            'plugin_name' => 'Hidove图床图片上传插件',
            'plugin_uri' => 'https://blog.hidove.cn',
            'description' => 'Hidove图床图片上传插件。上传您的图片至Hidove图床，并在您的站点中显示。',
            'author_uri' => 'https://blog.hidove.cn',
        );
        if ($key) {
            return isset($trs[$key]) ? $trs[$key] : false;
        }

        return $trs;
    }
    public static function init()
    {

        include __DIR__ . '/core/core-functions.php';
        include __DIR__ . '/core/core-options.php';
        include __DIR__ . '/core/core-features.php';

        self::tdomain();

        /** update */
        if (is_admin())
        //self::update();

        /**
         * options
         */
        {
            add_filter('plugin_options_default_' . __CLASS__, __CLASS__ . '::options_default');
        }

        add_filter('plugin_options_save_' . __CLASS__, __CLASS__ . '::backend_options_save');
        /**
         * ajax
         */
        add_action('wp_ajax_' . __CLASS__, __CLASS__ . '::process');
        /**
         * orther
         */
        add_action('admin_init', __CLASS__ . '::meta_box_add');
        add_action('save_post', __CLASS__ . '::meta_box_save');
        add_action('wp_footer', __CLASS__ . '::footer_info');
        // add_filter('post_thumbnail_html', __CLASS__ . '::filter_post_thumbnail_html', 10, 5);
        // add_filter('get_post_metadata', __CLASS__ . '::filter_get_post_metadata', 10, 4);

        /**
         * settings
         */
        add_action('plugin_base_settings_' . __CLASS__, __CLASS__ . '::display_backend_basic_settings');

        add_action('plugin_advanced_settings_' . __CLASS__, __CLASS__ . '::display_backend_advanced_settings');

        add_action('plguin_help_settings_' . __CLASS__, __CLASS__ . '::display_backend_help_setting');

        add_action('admin_head', __CLASS__ . '::backend_head');

    }

    /**
     * set_options_authorize
     *
     * @return
     * @version 1.0.1
     */
    private static function set_options_authorize($args)
    {
        $defaults = array(
            'access_token' => null,
            'expires_in' => null,
        );
        $r = wp_parse_args($args, $defaults);
        self::set_options(self::$key_authorization, $r);
    }
    private static function set_options()
    {
        return call_user_func_array(array(self::$plugin_options, 'set_options'), func_get_args());
    }
    private static function get_options($key = null)
    {
        static $caches = null;
        if ($caches === null) {
            $caches = call_user_func(self::$plugin_options . '::get_options');
        }

        if ($key) {
            return isset($caches[$key]) ? $caches[$key] : false;
        }

        return $caches;
    }
    /**
     * Get tietuku image pattern
     *
     * @return string Pattern
     * @version 1.0.0
     */
    private static function get_tietukuimg_pattern()
    {
        return '/\w+:\/\/\w+\.tietukuimg\.cn\/\w+\/\w+\.\w+/i';
    }
    private static function get_localimg_pattern($with_http = false)
    {
        $upload_dir = wp_upload_dir();
        $prefix = $with_http ? addcslashes($upload_dir['baseurl'] . self::$basedir_backup, '/.') : null;
        return '/' . $prefix . '[0-9]+\-\w+\-\w+\-\w+\.\w+/i';
    }

    /**
     * Get remote images url
     *
     * @param string $content Post content or post meta
     * @return array Images url of array/An empty array
     * @version 1.0.0
     */
    private static function get_tietukuimg_urls($content = null)
    {
        if (!$content) {
            return false;
        }

        preg_match_all(self::get_tietukuimg_pattern(), $content, $matches);
        return $matches[0];
    }
    /**
     * Get local image path by tietuku image url
     *
     * @param string $tietukuimg_url tietuku image url
     * @param int $postid The post id of image
     * @return string Local image url
     * @version 1.0.0
     */
    private static function get_localimg_path_by_tietukuimg($tietukuimg_url, $postid = null)
    {
        return false;

        if (stripos($tietukuimg_url, 'tietukuimg.cn') === false) {
            return false;
        }

        if (!$postid) {
            global $post;
            $postid = $post->ID;
        }
        $upload_dir = wp_upload_dir();
        $backup_dir = $upload_dir['basedir'] . self::$basedir_backup;
        $basename = implode('-', array(
            $postid,
            self::get_file_url_meta('subdomain', $tietukuimg_url),
            self::get_file_url_meta('size', $tietukuimg_url),
            self::get_file_url_meta('basename', $tietukuimg_url),
        ));
        $file_path = $backup_dir . $basename;
        return $file_path;
    }
    private static function get_size_url_by_size($url, $size)
    {
		return $url;
		
        /** check is tietukuimg.cn */
        if (stripos($url, 'tietukuimg.cn') !== false) {
            $ori_size = self::get_file_url_meta('size', $url);
        } else {
            static $home_url;
            if (!$home_url) {
                $home_url = home_url();
            }

            if (strpos($url, $home_url) === false) {
                return $url;
            }

            $ori_size = self::get_local_file_meta('size', $url);
        }
        if (!$ori_size) {
            return $url;
        }

        return str_replace($ori_size, $size, $url);
    }
    /**
     * Get local image url by tietuku image url
     *
     * @param string $tietukuimg_url tietuku image url
     * @param int $postid The post id of image
     * @return string Http image url
     * @version 1.0.0
     */
    private static function get_localimg_url_by_tietukuimg($tietukuimg_url, $postid = null)
    {
        return false;

        if (stripos($tietukuimg_url, 'tietukuimg.cn') === false) {
            return false;
        }

        if (!$postid) {
            global $post;
            $postid = $post->ID;
        }
        $upload_dir = wp_upload_dir();
        $backup_dir = $upload_dir['baseurl'] . self::$basedir_backup;
        $basename = implode('-', array(
            $postid,
            self::get_file_url_meta('subdomain', $tietukuimg_url),
            self::get_file_url_meta('size', $tietukuimg_url),
            self::get_file_url_meta('basename', $tietukuimg_url),
        ));
        $file_path = $backup_dir . $basename;
        return $file_path;
    }
    /**
     * process
     *
     * @params
     * @return
     * @version 1.0.1
     */
    public static function process()
    {
        $output = null;
        /**
         * get action type
         */
        $type = isset($_GET['type']) ? $_GET['type'] : null;
        /**
         * $options
         */
        $options = self::get_options();
        /**
         * set timeout limit is 0
         */
        @set_time_limit(0);
        switch ($type) {
            case 'web':

                $url = $_POST['url'];
                $ak = self::get_ak(); // app key
                $sk = self::get_sk(); // secret key
                include __DIR__ . '/sdk/sdk.php';

                $upload = new TTKClient($ak, $sk);
                $res = $upload->uploadFromWeb(self::get_aid(), $url, self::get_options('is_ssl'));

                $res = json_decode($res, true);

                $return[status] = $res[linkurl] ? 'success' : 'fail';
                switch (self::get_options('styles')) {
                    case 'linkurl':$res[url] = $res[linkurl];
                        break;
                    case 's_url':$res[url] = $res[s_url];
                        break;
                    case 't_url':$res[url] = $res[t_url];
                        break;
                    default:$res[url] = $res[s_url];
                        break;
                }
                $return[img_url] = $res[url];
                $return[s_url] = $res[s_url];
                $return[t_url] = $res[t_url];
                echo json_encode($return);

                break;
            /**
                 * upload pic
                 */
            case 'upload':

                $file = isset($_FILES['file']) ? $_FILES['file'] : '';

                $file_name = isset($file['name']) ? $file['name'] : null;
                $file_type = isset($file['type']) ? explode('/', $file['type']) : '';

                $file_type = !empty($file_type) ? $file_type[1] : null;
                $ext = !empty($file_type) ? '.' . $file_type : '';
                $tmp_name = isset($file['tmp_name']) ? $file['tmp_name'] : null;

                /**
                 * check upload error
                 */
                if (!isset($file['error']) || $file['error'] != 0) {
                    $output['status'] = 'error';
                    $output['msg'] = sprintf(__('上传失败，错误代码： %s', __CLASS__), $file['code']);
                    $output['code'] = 'file_has_error_code';
                    self::die_json_format($output);
                }
                /**
                 * check file params
                 */
                if (!$file_name || !$file_type || !$tmp_name) {
                    $output['status'] = 'error';
                    $output['msg'] = __('缺少参数', __CLASS__);
                    $output['code'] = 'not_enough_params';
                    self::die_json_format($output);
                }
                /**
                 * check file type
                 */
                if (!in_array($file_type, self::$allow_types)) {
                    $output['status'] = 'error';
                    $output['msg'] = __('文件类型非法', __CLASS__);
                    $output['code'] = 'invalid_file_type';
                    self::die_json_format($output);
                }
                $ak = self::get_options('ak'); // app key
                $sk = self::get_options('sk'); // secret key
                include __DIR__ . '/sdk/sdk.php';

                $upload = new TTKClient($ak, $sk);
                $res = $upload->uploadFile(self::get_options('aid'), $tmp_name, $file_name, self::get_options('is_ssl'));
                $res = json_decode($res, true);

                $return[status] = $res[linkurl] ? 'success' : 'fail';
                switch (self::get_options('styles')) {
                    case 1:$res[url] = $res[linkurl];
                        break;
                    case 2:$res[url] = $res[s_url];
                        break;
                    case 3:$res[url] = $res[t_url];
                        break;
                    default:$res[url] = $res[s_url];
                        break;
                }
                $return[img_url] = $res[url];
                $return[filename] = $file_name;
                $return[s_url] = $res[s_url];
                $return[t_url] = $res[t_url];
                echo json_encode($return);
                //{"status":"success","img_url":"###"}

                break;

            default:
                $output['status'] = 'error';
                $output['code'] = 'invalid_param';
                $output['msg'] = __('Invalid param.', __CLASS__);
        }
        self::die_json_format($output);
    }
    private static function die_json_format()
    {
        header('Content-Type: application/json');
        die(call_user_func_array(array(self::$plugin_functions, 'json_format'), func_get_args()));
    }
    /**
     * get_config
     *
     * @param
     * @return
     * @version 1.0.0
     */
    public static function get_config($key)
    {
        return call_user_func(array(self::$plugin_functions, 'authcode'), self::$wb[$key]);
    }

    /**
     * get_file_url_meta
     *
     * @param string $key
     * @param string $file_url
     * @return string image size
     * @version 1.0.1
     * @example
     **/
    private static function get_file_url_meta($key = nulll, $file_url = null)
    {
        $file_url = $file_url ? $file_url : self::$file_url;

        if (!$key || stripos($file_url, 'tietuku.com') === false || !$file_url) {
            return false;
        }

        $file_obj = explode('/', $file_url);
        $len = count($file_obj);
        /**
         * file eg
         */
        switch ($key) {
            /**
                 * basename eg. 5dd1e978jw1eo083cx0sgj218g0xck6d.jpg
                 */
            case 'basename':
                $return = $file_obj[$len - 1];
                break;
            /**
                 * size eg. square
                 */
            case 'size':
                $return = $file_obj[$len - 2];
                break;
            /**
                 * id/filename eg. 5dd1e978jw1eo083cx0sgj218g0xck6d
                 */
            case 'id':
            case 'filename':
                $id = explode('.', $file_obj[$len - 1]); /** fuckyou php53 */
                $return = $id[0];
                break;
            /**
                 * ext eg. jpg
                 */
            case 'ext':
                $ext = explode('.', $file_obj[$len - 1]); /** fuckyou php53 */
                $return = $ext[1];
                break;
            /**
                 * domain eg. ww2
                 */
            case 'subdomain':
                $return = $file_obj[$len - 3];
                $return = explode('.', $return); /** fucku php53 */
                $return = $return[0];
                break;
            default:
                return false;

        }
        return $return;
    }

    /**
     * Get local file meta
     *
     * @param string $basename The local file basename. eg. 1-ww2-square-xxx.jpg
     * @return string
     * @version 1.0.1
     */
    private static function get_local_file_meta($type, $filename)
    {
        $basename = basename($filename);
        $basename_obj = explode('-', $basename);
        switch ($type) {
            /**
                 * post_id eg. 1
                 */
            case 'post_id':
            case 'postid':
                return isset($basename_obj[0]) ? $basename_obj[0] : null;
            /**
                 * domain
                 */
            case 'subdomain':
                return isset($basename_obj[1]) ? $basename_obj[1] : null;
            /**
                 * size
                 */
            case 'size':
                return isset($basename_obj[2]) ? $basename_obj[2] : null;
            /**
                 * basename eg. xxx.jpg
                 */
            case 'basename':
                return isset($basename_obj[3]) ? $basename_obj[3] : null;
            /**
                 * id/filename eg. xxx
                 */
            case 'id':
                $return = explode('.', $basename_obj[3]);
                return isset($return[0]) ? $return[0] : null;
            /**
                 * ext
                 */
            case 'ext':
                $return = explode('.', $ar[3]);
                return isset($return[1]) ? $return[1] : null;
            default:
                return false;
        }
    }
    /**
     * Get has been backed up files
     *
     * @param string $type File meta type
     * @return array
     * @version 1.0.0
     */
    private static function get_backup_files($type = null)
    {
        /**
         * @see https://codex.wordpress.org/Function_Reference/wp_upload_dir
         */
        $upload_dir = wp_upload_dir();
        $backup_dir = $upload_dir['basedir'] . self::$basedir_backup;
        if (empty(self::$cache_backup_files)) {
            self::$cache_backup_files = glob($backup_dir . '*');
        }
        if (empty(self::$cache_backup_files)) {
            return false;
        } else {
            self::$cache_backup_files = array_unique(self::$cache_backup_files);
        }

        $returns = array();
        foreach (self::$cache_backup_files as $img_path) {
            if (is_file($img_path)) {
                $returns[] = $type ? self::get_local_file_meta($type, $img_path) : $img_path;
            }
        }
        return $returns;

    }
    private static function get_localimg_urls_by_content($content)
    {
        /**
         * with http
         */
        preg_match_all(self::get_localimg_pattern(true), $content, $matches);
        return $matches[0];
    }

    public static function options_default($options)
    {
        $options['feature_meta'] = self::$thumbnail_external_key;
        $options['is_ssl'] = is_ssl() ? 1 : 0;
        $options['thumbnail-size'] = 'thumb150';
        return $options;
    }
    /**
     * backend_options_save
     *
     * @params array options
     * @return array options
     * @version 1.0.2
     */
    public static function backend_options_save(array $options)
    {

        if (!isset($_POST[__CLASS__])) {
            return $options;
        }

        $authorization = (array) self::get_options(self::$key_authorization);

        $options = (array) $_POST[__CLASS__];

        /**
         * is ssl
         */
        $options['is_ssl'] = isset($options['is_ssl']) ? $options['is_ssl'] : 0;

        $options['feature_meta'] = isset($_POST[__CLASS__]['feature_meta']) && trim($_POST[__CLASS__]['feature_meta']) !== '' ? trim($_POST[__CLASS__]['feature_meta']) : self::$thumbnail_external_key;
        $new_meta = $options['feature_meta'];
        $old_thumbnail_size = $options['old-thumbnail-size'];
        $new_thumbnail_size = $options['thumbnail-size'];

        global $post;
        /**
         * check the new and old meta key from $_POST
         */

        $old_meta = $options['old_meta'];
        if ($old_meta !== $new_meta) {
            /**
             * update the old meta key
             */
            $query = new WP_Query(array(
                'meta_key' => $old_meta,
            ));
            if ($query->have_posts()) {
                foreach ($query->posts as $post) {
                    $meta_v = get_post_meta($post->ID, $old_meta, true);
                    delete_post_meta($post->ID, $old_meta);
                    add_post_meta($post->ID, $new_meta, $meta_v);
                }
                wp_reset_postdata();
            }
        }
        /**
         * update thumnail size
         */
        if ($old_thumbnail_size !== $new_thumbnail_size) {
            $query = new WP_Query(array(
                'meta_key' => $new_meta,
            ));
            if ($query->have_posts()) {
                foreach ($query->posts as $post) {
                    $old_thumbnail_url = get_post_meta($post->ID, $new_meta, true);
                    if (!empty($old_thumbnail_meta)) {
                        update_post_meta($post->ID, $new_meta, self::get_size_url_by_size($old_thumbnail_url, $new_thumbnail_size));
                    }
                }
                wp_reset_postdata();
            }
        }
        $options[self::$key_authorization] = $authorization;
        return $options;
    }
    public static function display_backend_basic_settings()
    {
        $options = self::get_options();
        $auth_link = self::get_authorize_uri();
        ?>

		<?php

        $checked_is_ssl = isset($options['is_ssl']) && $options['is_ssl'] == 1 ? ' checked ' : null;
        ?>
		<fieldset>
			<legend>插件设置</legend>
			<table class="form-table">
				<tbody>
					<tr>
						<th><label for="api">上传接口</label></th>
						<td>
							<label for="api"><input id="api" name="<?php echo __CLASS__; ?>[api]" type="text" class="regular-text" value="<?php echo self::get_api(); ?>"/><input type="hidden" name="<?php echo __CLASS__; ?>[old_api]" value="<?php echo self::get_api(); ?>"/></label>
						</td>
					</tr>
                    <tr>
                        <th><label for="token">您的token</label></th>
                        <td>
                            <label for="token"><input id="token" name="<?php echo __CLASS__; ?>[token]" type="text" class="regular-text" value="<?php echo self::get_token(); ?>"/><input type="hidden" name="<?php echo __CLASS__; ?>[old_token]" value="<?php echo self::get_token(); ?>"/></label>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="folder">目录ID</label></th>
                        <td>
                            <label for="folder"><input id="folder" name="<?php echo __CLASS__; ?>[folder]" type="text" class="regular-text" value="<?php echo self::get_folder(); ?>"/><input type="hidden" name="<?php echo __CLASS__; ?>[old_folder]" value="<?php echo self::get_folder(); ?>"/></label>
                        </td>
                    </tr>
                    <tr>
						<th><label for="api_type">公共接口类型</label></th>
						<td>
							<label for="api_type"><input id="api_type" name="<?php echo __CLASS__; ?>[api_type]" type="text" class="regular-text" value="<?php echo self::get_api_type(); ?>"/><input type="hidden" name="<?php echo __CLASS__; ?>[old_api_type]" value="<?php echo self::get_api_type(); ?>"/></label>
						</td>
                    </tr>
                    <tr>
                        <th><label for="private_storage">私有储存类型</label></th>
                        <td>
                            <label for="private_storage"><input id="private_storage" name="<?php echo __CLASS__; ?>[private_storage]" type="text" class="regular-text" value="<?php echo self::get_private_storage(); ?>"/><input type="hidden" name="<?php echo __CLASS__; ?>[old_private_storage]" value="<?php echo self::get_private_storage(); ?>"/></label>
                        </td>
                    </tr>

				</tbody>
			</table>
		</fieldset>
		<?php
}
    public static function display_backend_advanced_settings()
    {
        $auto_backup = isset($options['auto_backup']) ? ' checked="checked" ' : null;

        $posts_count = wp_count_posts();
        $backup_dir = wp_upload_dir();
        $backup_dir = $backup_dir['basedir'] . self::$basedir_backup;

    }
    public static function backend_head()
    {
        if (!call_user_func(array(self::$plugin_options, 'is_options_page'))) {
            return false;
        }

        echo call_user_func_array(array(self::$plugin_features, 'get_plugin_css'), array('backup-restore', 'normal'));

        echo call_user_func_array(array(self::$plugin_features, 'get_plugin_js'), array('backup-restore', false));
        ?>
		<script>
		(function(){
			var hidoveimage = new hidoveimage_admin();
			hidoveimage.config.lang.E00001 = '<?php _e('Error code: ', __CLASS__);?>';
			hidoveimage.config.lang.E00002 = '<?php _e('Program error, can not continue to operate. Please try again or contact author. ', __CLASS__);?>';
			hidoveimage.config.lang.E00003 = '<?php _e('Program error, can not continue to operate. Please try again or contact author. ', __CLASS__);?>';
			hidoveimage.config.lang.M00001 = '<?php _e('Getting backup config data, please wait... ', __CLASS__);?>';
			hidoveimage.config.lang.M00002 = '<?php _e('Current processing: ', __CLASS__);?>';
			hidoveimage.config.lang.M00003 = '<?php _e('Downloading, you can restore the pictures to post after the download is complete. ', __CLASS__);?>';
			hidoveimage.config.lang.M00005 = '<?php _e('Download completed, you can perform a restore operation.', __CLASS__);?>';
			hidoveimage.config.lang.M00006 = '<?php _e('Current file has been downloaded, skipping it.', __CLASS__);?>';
			hidoveimage.config.lang.M00010 = '<?php _e('The data is being restored , please wait...  ', __CLASS__);?>';
			hidoveimage.config.process_url = '<?php echo call_user_func(array(self::$plugin_features, 'get_process_url'), array('action' => __CLASS__)); ?>';
			hidoveimage.init();
		})();
		</script>
		<?php
}
    public static function display_backend_help_setting()
    {
        if (!self::$plugin_data) {
            self::$plugin_data = call_user_func(self::$plugin_options . '::get_plugin_data');
        }

        ?>
		<style>.form-table th,.form-table td{padding:5px 10px 10px 0; }</style>
		<fieldset>
			<legend>帮助信息</legend>
			<table class="form-table">
				<tbody>
					<tr>
						<th>插件名称：</th>
						<td>
							<strong><?php echo self::$plugin_data['Name']; ?></strong>
						</td>
					</tr>
					<tr>
						<th>版本号：</th>
						<td>
							<?php echo self::$plugin_data['Version']; ?>
						</td>
					</tr>
					<tr>
						<th>更新日期：</th>
                        <td>
                            2020年6月24日
						</td>
					</tr>
					<tr>
						<th>插件介绍：</th>
						<td>
							<p><?php echo self::$plugin_data['Description']; ?></p>
							<p>详细说明：<a href="https://img.abcyun.co" target="_blank">https://img.abcyun.co</a></p>
						</td>
					</tr>
					<tr>
						<th scope="row">反馈和技术支持：</th>
						<td>
							<p>E-Mail:loliconla@qq.com</p>

						</td>
					</tr>

				</tbody>
			</table>
		</fieldset>
		<?php
}
    /**
     * meta_box_add
     *
     * @return n/a
     * @version 1.0.0
     */
    public static function meta_box_add()
    {
        $screens = array('post', 'page');

        foreach ($screens as $screen) {

            add_meta_box(
                __CLASS__,
                'Hidove图床上传插件',
                __CLASS__ . '::meta_box_display',
                $screen
            );
            /**
             * add for thumbnail
             */
            add_meta_box(
                __CLASS__ . '-thumbnail',
                __('Hidove图床上传插件 - 特色图设置', __CLASS__),
                __CLASS__ . '::meta_box_display_thumbnail',
                $screen,
                'side'
            );
        }
    }
    /**
     * meta_box_save
     *
     * @params int $post_id
     * @return n/a
     * @version 1.0.1
     */
    public static function meta_box_save($post_id)
    {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE || !current_user_can('edit_post', $post_id)) {
            return;
        }

        $post_meta = isset($_POST[__CLASS__]) ? $_POST[__CLASS__] : null;

        if (!$post_meta) {
            return;
        }

        $custom_thumbnail_meta = self::get_custom_thumbnail_meta();

        if (!empty($post_meta['thumbnail-url'])) {
            update_post_meta($post_id, $custom_thumbnail_meta, $post_meta['thumbnail-url']);
        } else {
            delete_post_meta($post_id, $custom_thumbnail_meta);
        }

    }

    public static function filter_get_post_metadata($content, $object_id, $meta_key, $single)
    {
        if ($meta_key !== '_thumbnail_id') {
            return $content;
        }

        $url = self::get_post_thumbnail_url($object_id, $single);
        return $url ? $url : $content;
    }
    public static function get_post_thumbnail_url($post_id, $single)
    {
        static $caches = array();
        if (isset($caches[$post_id])) {
            return $caches[$post_id];
        }

        $caches[$post_id] = get_post_meta($post_id, self::get_custom_thumbnail_meta(), $single);

        return $caches[$post_id];
    }
    public static function get_custom_thumbnail_meta()
    {
        return self::get_options('feature_meta');
    }
    public static function get_sk()
    {
        return self::get_options('sk');
    }
    public static function get_token()
    {
        return self::get_options('token');
    }

    public static function get_api()
    {
        return self::get_options('api');
    }
    public static function get_api_type()
    {
        return self::get_options('api_type');
    }
    public static function get_private_storage()
    {
        return self::get_options('private_storage');
    }
    public static function get_folder()
    {
        return self::get_options('folder');
    }
    public static function get_styles()
    {
        return self::get_options('styles');
    }
    public static function get_custom_thumbnail_size()
    {
        return self::get_options('thumbnail-size');
    }
    public static function filter_post_thumbnail_html($html, $post_id, $post_thumbnail_id, $size, $attr)
    {

        $custom_url = self::get_post_thumbnail_url($post_id, true);

        if (empty($custom_url)) {
            return $html;
        }

        $post_title = esc_attr(get_the_title($post_id));

        $custom_url = self::get_size_url_by_size($custom_url, self::get_custom_thumbnail_size());

        return '<img src="' . $custom_url . '" alt="' . $post_title . '" title="' . $post_title . '" class="post-thumbnail">';

    }
    /**
     * get_authorize_uri
     *
     * @return string
     * @version 1.0.0
     */
    private static function get_authorize_uri()
    {
        $authorize_uri_obj = array(
            'uri' => call_user_func(array(self::$plugin_features, 'get_process_url'), array(
                'action' => __CLASS__,
                'type' => 'set_authorize',
            )),
        );
        $authorize_uri = self::get_config(2) . http_build_query($authorize_uri_obj);
        return $authorize_uri;
    }
    public static function get_max_upload_size()
    {
        $max_mb = ini_get('upload_max_filesize');
        if (stripos($max_mb, 'm') === false) {
            /**
             * 1024*2048
             */
            return 2097152;
        } else {
            return 1048576 * (int) $max_mb;
        }
    }
    public static function get_sizes($key = null)
    {
        return self::get_options('thumbnail-size');
    }
    /**
     * meta_box_display
     *
     * @return string HTML
     * @version 1.0.2
     */
    public static function meta_box_display()
    {
        //生成token发送到页面
        $param['sk'] = self::get_sk();
        $api = self::get_api();
        $token = self::get_token();
        $apiType = self::get_api_type();
        $privateStorage = self::get_private_storage();
        $folder = self::get_folder();
        $is_ssl = self::get_options('is_ssl');
        $param['httptype'] = ($is_ssl == 1) ? 2 : 0;
        $style = self::get_styles();
        include __DIR__ . '/sdk/sdk.php';
        $obj = new TieTuKuToken($param['ak'], $param['sk']);
        $param['from'] = 'web';
        echo <<<HIDOVE
<script>
    var api='$api';
    var token='$token';
    var api_type='$apiType';
    var private_storage='$privateStorage';
    var folder='$folder';
    
    var style='$style';
</script>

HIDOVE;

        global $post;
        $options = self::get_options();
        /**
         * authorize_uri
         */
        $authorize_uri = self::get_authorize_uri();
        $authorized_js = true;

        echo call_user_func_array(array(self::$plugin_features, 'get_plugin_css'), array('post', 'normal'));

        ?>
		<script src="<?php echo call_user_func(array(self::$plugin_features, 'get_plugin_js'), 'post'); ?>"></script>
		<script>
		var oo = new hidoveimage();
		oo.config.process_url = '<?php echo call_user_func(array(self::$plugin_features, 'get_process_url'), array(
            'action' => __CLASS__,
            'post_id' => $post->ID,
        )); ?>';
		oo.config.post_id = <?php echo $post->ID; ?>;
		oo.config.lang.E00001 = '错误';
		oo.config.lang.E00002 = '上传失败';
		oo.config.lang.E00003 = '';
		oo.config.lang.M00001 = '<?php _e('正在上传第 {0}/{1}, 请稍候...', __CLASS__);?>';
		oo.config.lang.M00002 = '<?php _e('{0} 文件已上传', __CLASS__);?>';
		oo.config.lang.M00003 = '<?php _e('图片网址: ', __CLASS__);?>';
		oo.config.lang.M00004 = 'ALT属性';
		oo.config.lang.M00005 = '设置ALT属性文本';
		oo.config.lang.M00006 = '控制';
		oo.config.lang.M00007 = '插入到编辑器';
		oo.config.lang.M00008 = '插入到编辑器（不带A标签）';
		oo.config.lang.M00009 = '作为特色图像';
		oo.config.authorized = true;
		oo.config.show_title = <?php echo isset($options['img-title-enabled']) ? 'true' : 'false'; ?>;
		oo.config.max_upload_size = <?php echo self::get_max_upload_size(); ?>;
		oo.config.thumbnail_size = '<?php echo $options['thumbnail-size']; ?>';
		<?php $stylestemp = explode('|', self::get_styles());
        foreach ($stylestemp as $v) {
            $styles[$v] = $v;
        }?>oo.config.sizes = <?php echo json_encode($styles); ?>;
		oo.init();
		var default_style=<?php echo json_encode($stylestemp); ?>;
		var default_large_size='<?php echo $options['thumbnail-size']; ?>';
		</script>
		<div id="hidoveimage-container">
			<div id="hidoveimage-area-upload">
				<div id="hidoveimage-loading-tip">
					<?php echo self::status_tip('loading', 'middle', '插件正在载入...'); ?>
				</div>

				<div id="hidoveimage-progress"><div id="hidoveimage-progress-tx"></div><div id="hidoveimage-progress-bar"></div></div>
				<div class="button-primary" id="hidoveimage-add">
					<span class="dashicons dashicons-format-image"></span>
					选择或拖动图片上传
					<input type="file" id="hidoveimage-file" accept="image/gif,image/jpeg,image/png" multiple="true" />
				</div>
				<div style="text-align:center;" id="hidoveimage-web">
					<big style="font-weight:bold;">网络图片:</big><input type="text" style="width:70%" id="hidoveimage-webtext" />
					<input type="button" class="button button-primary button-large" id="webbtn" value="上传网络图片" />
				</div>
				<div id="hidoveimage-completion-tip"></div>
				<div id="hidoveimage-error-file-tip">
					<span class="des"><?php echo esc_html(__('Detects that files can not be uploaded:')); ?></span>
					<span id="hidoveimage-error-files"></span>
				</div>
				<div id="hidoveimage-tools">
					<a id="hidoveimage-insert-list-with-link" href="javascript:;" class="button button-primary">插入到编辑器</a>
					<a id="hidoveimage-insert-list-without-link" href="javascript:;" class="button">插入到编辑器（不带A标签）</a>

					<select id="hidoveimage-split">
						<option value="0">不要分页符</option>
						<option value="nextpage">使用分页符</option>

					</select>


					<a href="javascript:;" id="hidoveimage-clear-list">清除列表</a>
				</div>
			</div>
			<div id="hidoveimage-tpl-container"></div>
		</div>
	<?php
}
    public static function meta_box_display_thumbnail()
    {
        global $post;
        $thumbnail_url = get_post_meta($post->ID, self::get_custom_thumbnail_meta(), true);

        ?>
		<div id="hidoveimage-thumbnail-container">
			<div id="<?php echo __CLASS__; ?>-thumbnail-tip" class="<?php echo empty($thumbnail_url) ? '' : 'hide'; ?>"><?php echo self::status_tip('info', __('尚未设置自定义缩略图')); ?></div>
			<div id="<?php echo __CLASS__; ?>-thumbnail-preview">
				<?php if (!empty($thumbnail_url)) {?>
					<img src="<?php echo $thumbnail_url; ?>" alt="自定义缩略图预览">
				<?php }?>
			</div>
			<a href="javascript:;" id="hidoveimage-thumbnail-remove" class="<?php echo empty($thumbnail_url) ? 'hide' : ''; ?>">移除缩略图</a>
			<input type="hidden" name="<?php echo __CLASS__; ?>[thumbnail-url]" id="hidoveimage-thumbnail-url" value="<?php echo $thumbnail_url; ?>">
		</div>
		<?php
}
    /**
     * status_tip
     *
     * @param string|mixed
     * @return string
     * @version 1.0.0
     */
    public static function status_tip()
    {
        return call_user_func_array(array(self::$plugin_functions, 'status_tip'), func_get_args());
    }
    /**
     * footer_info
     */
    public static function footer_info()
    {
        echo '';
    }
}
?>
