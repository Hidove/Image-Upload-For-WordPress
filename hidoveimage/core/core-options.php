<?php
namespace hidoveimage\core;

plugin_options::init();
class plugin_options
{
    public static $iden = 'hidoveimage';

    public static $opts;

    /**
     * init
     *
     * @return
     * @version 1.0.0
     */
    public static function init()
    {

        add_filter('plugin_action_links_' . plugin_basename(dirname(__DIR__) . '/' . self::$iden . '.php'), __CLASS__ . '::plugin_action_links');

        add_action('admin_menu', __CLASS__ . '::add_page');

        add_action('wp_ajax_options_save_' . self::$iden, __CLASS__ . '::process');

        add_action('admin_head', __CLASS__ . '::backend_head', 9);
    }
    public static function plugin_action_links($links)
    {
        return array_merge(array(
            'settings' => '<a href="' . admin_url('plugins.php?page=' . self::get_options_page_slug()) . '">设置</a>',
        ), $links);
    }
    public static function get_plugin_data($key = null)
    {
        static $caches = array();

        if (empty($caches)) {
            $caches = get_plugin_data(dirname(__DIR__) . '/' . self::$iden . '.php', false);
        }

        if ($key) {
            return isset($caches[$key]) ? $caches[$key] : null;
        } else {
            return $caches;
        }
    }
    public static function backend_head()
    {
        if (!self::is_options_page()) {
            return false;
        }

        /**
         * load js and css
         */
        echo plugin_features::get_plugin_css('backend', 'normal');
        echo plugin_features::get_plugin_js('jquery.kandytabs', false);
        echo plugin_features::get_plugin_js('backend', false);
        ?>
		<script>
		(function(){
			plugin_backend({
				done : function($btn,$cont,$tab){
					<?php do_action('plugin_after_backend_tab_init_' . self::$iden);?>
				},
				custom : function(b,c,i,t){
					<?php do_action('plugin_after_backend_tab_custom_' . self::$iden);?>
				},
				tab_title : '<?php echo self::get_plugin_data('Name'); ?> 插件设置'
			});
		})();
		</script>
		<?php
}
    public static function add_page()
    {
        if (!self::current_user_can('manage_options')) {
            return false;
        }

        add_plugins_page(
            sprintf(__('%s 设置', self::$iden), self::get_plugin_data('Name')),
            sprintf(__('%s 设置', self::$iden), self::get_plugin_data('Name')),
            'manage_options',
            self::get_options_page_slug(),
            __CLASS__ . '::display_backend'
        );
    }
    //public static function status_tip(){
    //    return call_user_func_array(array(self::$plugin_functions,'status_tip'),func_get_args());
    //}
    public static function display_backend()
    {

        ?>
		<div class="wrap">
			<?php if (isset($_GET['updated'])) {?>
				<div id="settings-updated">
					<?php echo plugin_functions::status_tip('success', __('保存成功', self::$iden)); ?>
				</div>
			<?php }?>
			<form id="backend-options-frm" method="post" action="<?php echo plugin_features::get_process_url(array(
            'action' => 'options_save_' . self::$iden,
        )); ?>">

				<div class="backend-tab-loading"><?php echo plugin_functions::status_tip('loading', __('Loading, please wait...', self::$iden)); ?></div>

				<dl id="backend-tab" class="backend-tab">
					<dt title="<?php echo __('Plugin common settings.', self::$iden); ?>">
						<span class="dashicons dashicons-admin-generic"></span>
						<span class="tx">基本设置</span>
					</dt>
					<dd>
						<!-- the action of base_settings -->
						<?php do_action('plugin_base_settings_' . self::$iden);?>
					</dd><!-- BASE SETTINGS -->

					<dt>
						<span class="dashicons dashicons-editor-help"></span>
						<span class="tx">关于 & 帮助</span>
					</dt>
					<dd>
						<?php do_action('plguin_help_settings_' . self::$iden);?>
					</dd><!-- ABOUT and HELP -->
				</dl>
				<p>
					<input type="hidden" name="<?php echo self::$iden; ?>[nonce]" value="<?php echo wp_create_nonce(self::$iden); ?>">

					<button type="submit" class="button button-primary button-large"><span class="dashicons dashicons-yes"></span> 保存所有设置</button>

					<label for="options-restore" class="label-options-restore" title="插件有什么错误？尝试恢复。小心，插件选项将被清除！">
						<input id="options-restore" name="<?php echo self::$iden; ?>[restore]" type="checkbox" value="1"/>
						恢复插件默认选项 <span class="dashicons dashicons-backup"></span></i>
					</label>
				</p>
			</form>
		</div>
		<?php
}
    public static function process()
    {

        if (!isset($_POST[self::$iden]['nonce'])) {
            die();
        }

        if (!wp_verify_nonce($_POST[self::$iden]['nonce'], self::$iden)) {
            die();
        }

        self::options_save();

        wp_redirect(add_query_arg(
            'updated',
            true,
            self::get_url()
        ));
        die();
    }
    public static function get_url()
    {
        static $caches = array();
        if (!isset($caches[self::$iden])) {
            $caches[self::$iden] = admin_url('plugins.php?page=' . self::get_options_page_slug());
        }

        return $caches[self::$iden];
    }
    public static function current_user_can($key)
    {
        static $caches = array();
        if (!isset($caches[$key])) {
            $caches[$key] = current_user_can($key);
        }

        return $caches[$key];
    }
    public static function get_options_page_slug()
    {
        return self::$iden . '-core-options';
    }
    public static function is_options_page()
    {
        if (!self::current_user_can('manage_options')) {
            return false;
        }

        if (is_admin() && isset($_GET['page']) && $_GET['page'] === self::get_options_page_slug()) {
            return true;
        } else {
            return false;
        }
    }

    public static function get_options($key = null)
    {
        /** Default options hook */
        self::$opts = wp_parse_args(
            (array) get_option('plugin_options_' . self::$iden),
            apply_filters('plugin_options_default_' . self::$iden, array())
        );

        if ($key) {
            return isset(self::$opts[$key]) ? self::$opts[$key] : null;
        } else {
            return self::$opts;
        }
    }
    public static function set_options($key, $data)
    {
        self::$opts = self::get_options();
        self::$opts[$key] = $data;
        update_option('plugin_options_' . self::$iden, self::$opts);
        return self::$opts;
    }
    private static function options_save()
    {
        if (!self::current_user_can('manage_options')) {
            return false;
        }

        $options = apply_filters('plugin_options_save_' . self::$iden, array());

        /** Reset the options? */
        if (isset($_POST[self::$iden]['restore'])) {
            /** Delete theme options */
            delete_option('plugin_options_' . self::$iden);
        } else {
            update_option('plugin_options_' . self::$iden, $options);
        }
    }
    public static function delete_options($key)
    {
        self::$opts = self::get_options();
        if (!isset(self::$opts[$key])) {
            return false;
        }

        unset(self::$opts[$key]);
        update_option('plugin_options_' . self::$iden, self::$opts);
        return self::$opts;
    }
}

?>