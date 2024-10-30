<?php
if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

/**
    * WPLKLicenseKeyAutoloader Class
    *
    *
    * @since 1.0.0
    */


/*
Plugin Name: License Key Autoloader
Plugin URI: https://example.com/
Description: Automatically adds the license key value pair to the database for Kadence Blocks Pro and Kadence Pro theme when a button is clicked.
Version: 1.0
Author: Your Name
Author URI: https://example.com/
*/

class WPLKLicenseKeyAutoloader {
    const WPLAUNCHIFY_URL = 'https://wplaunchify.com';

    public function __construct() {
        add_action('admin_menu', array($this, 'launchkit_license_menu'));
        add_action('admin_init', array($this, 'license_key_autoloader_save'));
        add_action('admin_init', array($this, 'license_key_autoloader_check_default_key'));
    }

// Add the packages submenu
public function launchkit_license_menu() {
    $parent_slug = 'wplk'; // The slug of the LaunchKit plugin's main menu
    $page_slug = 'license'; // The slug for the submenu page
    $capability = 'manage_options';

    add_submenu_page(
        $parent_slug,
        __('LaunchKit License', 'launchkit-license'),
        __('LaunchKit License', 'launchkit-license'),
        //    '',  // Set the menu title to an empty string or null
        $capability,
        $page_slug,
        array($this, 'license_key_autoloader_page')
    );

    // Add a unique CSS class to the hidden submenu item
    add_action('admin_head', array($this, 'hide_license_submenu_item'));
}

    // Hide the empty space of the hidden packages submenu item
    public function hide_license_submenu_item() {
        global $submenu;
        $parent_slug = 'wplk';
        $page_slug = 'launchkit-license';

        if (isset($submenu[$parent_slug])) {
            foreach ($submenu[$parent_slug] as &$item) {
                if ($item[2] === $page_slug) {
                    $item[4] = 'launchkit-license-hidden';
                    break;
                }
            }
        }

        echo '<style>.launchkit-license-hidden { display: none !important; }</style>';
    }



    public function license_key_autoloader_page() {
        $last_email = get_option('lk_get_meta_last_email', '');
        $last_password = get_option('lk_get_meta_last_password', '');
        $last_login_date = get_option('lk_last_login_date', '');
        $logged_in = get_transient('lk_logged_in');
        $user_data = null;

        if (isset($_POST['lk_get_meta_submit'])) {
            $email = sanitize_email($_POST['email']);
            $password = sanitize_text_field($_POST['password']);

            update_option('lk_get_meta_last_email', $email);
            update_option('lk_get_meta_last_password', $password);

            $user_data = $this->lk_get_user_data($email, $password);

            if (isset($user_data['error']) && $user_data['error']) {
                $notice = '<div class="notice notice-error"><p>' . esc_html($user_data['message']) . '</p></div>';
            } elseif (isset($user_data['can_access_launchkit']) && $user_data['can_access_launchkit']) {
                set_transient('lk_logged_in', true, 30 * MINUTE_IN_SECONDS);
                set_transient('lk_user_data', $user_data, 30 * MINUTE_IN_SECONDS);
                update_option('lk_last_login_date', time());
                $logged_in = true;
            } else {
                $notice = '<div class="notice notice-error"><p>Failed to retrieve user data. Please try again.</p></div>';
            }
        }

        if (isset($_POST['lk_logout'])) {
            delete_transient('lk_logged_in');
            delete_transient('lk_user_data');
            $logged_in = false;
        }

        if ($logged_in) {
            $user_data = get_transient('lk_user_data');
        }

        if ($user_data) {
            $default_key = isset($user_data['default_key']) ? $user_data['default_key'] : '';
            $current_key_blocks = get_option('stellarwp_uplink_license_key_kadence-blocks-pro', $default_key);
            $current_key_theme = get_option('stellarwp_uplink_license_key_kadence-theme-pro', $default_key);
            $placeholder_text = ($current_key_blocks === $default_key && $current_key_theme === $default_key) ? '&#10004; Launchkit Key Is Installed And Activated' : 'Your Key Has Been Saved';
        }
        ?>
        <div class="wrap">
            <h1>Kadence Pro License Key</h1>
            <?php if ($logged_in) : ?>
                <p>Your LaunchKit key has been installed.<br/>You can replace it with your own below, and click "Save Your Own Key"</p>
                <form method="post" action="">
                    <?php wp_nonce_field('license_key_autoloader'); ?>
                    <p><label for="license_key">License Key</label></p>
                    <p><input type="text" name="license_key" id="license_key" class="regular-text" value="" placeholder="<?php echo esc_attr($placeholder_text); ?>" style="color: #888;"></p>
                    <p>
                        <?php submit_button('Use LaunchKit Key', 'secondary', 'reset_default', false); ?>
                        <?php submit_button('Save Your Own Key', 'primary', 'save_key', false); ?>
                    </p>
                </form>
                <form id="lk_logout_form" method="post" style="display: none;">
                    <input type="hidden" name="lk_logout" value="1">
                </form>
                <p><a href="#" onclick="document.getElementById('lk_logout_form').submit(); return false;">Log Out</a></p>
            <?php else : ?>
                <form method="post">
                    <table class="form-table" style="max-width: 300px;">
                        <tr>
                            <th style="width:50px;" scope="row"><label for="email">Email</label></th>
                            <td><input style="position:relative; width:90%" type="email" name="email" id="email" class="regular-text" value="<?php echo esc_attr($last_email); ?>" required></td>
                        </tr>
                        <tr>
                            <th style="width:50px;" scope="row"><label for="password">Password</label></th>
                            <td><input style="position:relative; width:90%" type="password" name="password" id="password" class="regular-text" value="<?php echo esc_attr($last_password); ?>" required></td>
                        </tr>
                    </table>
                    <p>
                        <?php submit_button('Log In', 'primary', 'lk_get_meta_submit', false, array('style' => 'width: 270px;')); ?>
                    </p>
                    <p><em>Use your login credentials from <a href="https://wplaunchify.com" target="_blank">WPLaunchify</a></em></p>
                    <?php if (!empty($last_login_date)) : ?>
                        <p><em>Last Login: <?php echo date('F j, Y', $last_login_date); ?></em></p>
                    <?php endif; ?>
                </form>
            <?php endif; ?>
            <?php if (isset($notice)) echo $notice; ?>
        </div>
        <?php
    }

    public function license_key_autoloader_save() {
        if (isset($_POST['save_key']) && check_admin_referer('license_key_autoloader')) {
            $license_key = sanitize_text_field($_POST['license_key']);
            if (!empty($license_key)) {
                update_option('stellarwp_uplink_license_key_kadence-blocks-pro', $license_key);
                update_option('stellarwp_uplink_license_key_kadence-blocks-pro_autoload', 'yes');
                update_option('stellarwp_uplink_license_key_kadence-theme-pro', $license_key);
                update_option('stellarwp_uplink_license_key_kadence-theme-pro_autoload', 'yes');
                wp_redirect(admin_url('admin.php?page=wplk&tab=license&settings-updated=true'));
                exit;
            }
        } elseif (isset($_POST['reset_default']) && check_admin_referer('license_key_autoloader')) {
            $user_data = get_transient('lk_user_data');
            $default_key = isset($user_data['default_key']) ? $user_data['default_key'] : '';
            update_option('stellarwp_uplink_license_key_kadence-blocks-pro', $default_key);
            update_option('stellarwp_uplink_license_key_kadence-blocks-pro_autoload', 'yes');
            update_option('stellarwp_uplink_license_key_kadence-theme-pro', $default_key);
            update_option('stellarwp_uplink_license_key_kadence-theme-pro_autoload', 'yes');
            wp_redirect(admin_url('admin.php?page=wplk&tab=license&settings-reset=true'));
            exit;
        }
    }

    public function license_key_autoloader_check_default_key() {
        $user_data = get_transient('lk_user_data');
        $default_key = isset($user_data['default_key']) ? $user_data['default_key'] : '';
        $current_key_blocks = get_option('stellarwp_uplink_license_key_kadence-blocks-pro');
        $current_key_theme = get_option('stellarwp_uplink_license_key_kadence-theme-pro');
        if (empty($current_key_blocks)) {
            update_option('stellarwp_uplink_license_key_kadence-blocks-pro', $default_key);
            update_option('stellarwp_uplink_license_key_kadence-blocks-pro_autoload', 'yes');
        }
        if (empty($current_key_theme)) {
            update_option('stellarwp_uplink_license_key_kadence-theme-pro', $default_key);
            update_option('stellarwp_uplink_license_key_kadence-theme-pro_autoload', 'yes');
        }
    }

    public function lk_get_user_data($email, $password) {
        $site_url = site_url();

        $response = wp_remote_post(self::WPLAUNCHIFY_URL . '/wp-json/wplaunchify/v1/user-meta', array(
            'body' => array(
                'email' => $email,
                'password' => $password,
                'site_url' => $site_url
            )
        ));

        if (is_wp_error($response)) {
            return ['error' => true, 'message' => 'Failed to connect to the WPLaunchify service. Please try again later.'];
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);

        if ($response_code !== 200) {
            $body = json_decode($response_body, true);
            if ($response_code === 401) {
                return ['error' => true, 'message' => 'Invalid credentials provided.'];
            } elseif ($response_code === 403) {
                return ['error' => true, 'message' => 'Access denied. You do not have the required permissions to access this feature.'];
            } else {
                return ['error' => true, 'message' => 'An unexpected error occurred. Please try again.'];
            }
        }

        return json_decode($response_body, true);
    }

} // instantiates class
new WPLKLicenseKeyAutoloader();