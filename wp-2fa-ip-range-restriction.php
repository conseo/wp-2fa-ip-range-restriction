<?php

/**
 * WP 2FA - IP Range Restriction
 *
 * @package       WP2FAIPRA
 * @author        Rupert Quaderer
 * @license       gplv2
 * @version       1.0.0
 *
 * @wordpress-plugin
 * Plugin Name:   WP 2FA - IP Range Restriction
 * Plugin URI:    https://conseo.ch
 * Description:   With this plugin you can disable the 2FA for the plugin WP 2FA
 * Version:       1.0.0
 * Author:        Rupert Quaderer
 * Author URI:    https://conseo.ch
 * Text Domain:   wp-2fa-ip-range-restriction
 * Domain Path:   /languages
 * License:       GPLv2
 * License URI:   https://www.gnu.org/licenses/gpl-2.0.html
 *
 * You should have received a copy of the GNU General Public License
 * along with WP 2FA - IP Range Restriction. If not, see <https://www.gnu.org/licenses/gpl-2.0.html/>.
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) exit;
require_once 'lib/ip-lib/ip-lib.php';

add_action('init', 'wp_2fa_ip_range_restricted');
function wp_2fa_ip_range_restricted()
{
  if (defined('WP_2FA_VERSION')) {
    add_filter(WP_2FA_PREFIX . 'skip_2fa_login_form', 'disable_with_ips');
  }
}

add_filter('plugin_action_links_wp-2fa-ip-range-restriction/wp-2fa-ip-range-restriction.php', 'nc_settings_link');
function nc_settings_link($links)
{
  $url = esc_url(add_query_arg(
    'page',
    'twofa-ip-range-restriction',
    get_admin_url() . 'options-general.php'
  ));
  $settings_link = "<a href='$url'>" . __('Settings') . '</a>';
  array_push(
    $links,
    $settings_link
  );
  return $links;
}


class TwoFAIPRangeRestriction
{
  private $twofa_ip_range_restriction_options;

  public function __construct()
  {
    add_action('admin_menu', array($this, 'twofa_ip_range_restriction_add_plugin_page'));
    add_action('admin_init', array($this, 'twofa_ip_range_restriction_page_init'));
  }

  public function twofa_ip_range_restriction_add_plugin_page()
  {
    add_options_page(
      'WP 2FA - IP Range Restriction', // page_title
      'WP 2FA - IP Range Restriction', // menu_title
      'manage_options', // capability
      'twofa-ip-range-restriction', // menu_slug
      array($this, 'twofa_ip_range_restriction_create_admin_page') // function
    );
  }

  public function twofa_ip_range_restriction_create_admin_page()
  {
    $this->twofa_ip_range_restriction_options = get_option('twofa_ip_range_restriction_option_name'); ?>

    <div class="wrap">
      <h2>WP 2FA - IP Range Restriction</h2>
      <p></p>
      <?php settings_errors(); ?>

      <form method="post" action="options.php">
        <?php
        settings_fields('twofa_ip_range_restriction_option_group');
        do_settings_sections('twofa-ip-range-restriction-admin');
        submit_button();
        ?>
      </form>
      My IP is: <?php echo getUserIPAddress(); ?>
    </div>
<?php }

  public function twofa_ip_range_restriction_page_init()
  {
    register_setting(
      'twofa_ip_range_restriction_option_group', // option_group
      'twofa_ip_range_restriction_option_name', // option_name
      array($this, 'twofa_ip_range_restriction_sanitize') // sanitize_callback
    );

    add_settings_section(
      'twofa_ip_range_restriction_setting_section', // id
      'Disable the 2FA if in this IP Range', // title
      array($this, 'twofa_ip_range_restriction_section_info'), // callback
      'twofa-ip-range-restriction-admin' // page
    );

    add_settings_field(
      'enable', // id
      'Disable 2FA for this IP range?', // title
      array($this, 'enable_callback'), // callback
      'twofa-ip-range-restriction-admin', // page
      'twofa_ip_range_restriction_setting_section' // section
    );

    add_settings_field(
      'exempt_ip_ranges', // id
      'Exempt IP ranges', // title
      array($this, 'exempt_ip_ranges_callback'), // callback
      'twofa-ip-range-restriction-admin', // page
      'twofa_ip_range_restriction_setting_section' // section
    );
    add_settings_field(
      'enforce_ip_ranges', // id
      'Enforced IP ranges', // title
      array($this, 'enforce_ip_ranges_callback'), // callback
      'twofa-ip-range-restriction-admin', // page
      'twofa_ip_range_restriction_setting_section' // section
    );
  }

  public function twofa_ip_range_restriction_sanitize($input)
  {
    $sanitary_values = array();
    if (isset($input['enable'])) {
      $sanitary_values['enable'] = $input['enable'];
    }

    if (isset($input['exempt_ip_ranges'])) {
      $sanitary_values['exempt_ip_ranges'] = sanitize_text_field($input['exempt_ip_ranges']);
    }

    if (isset($input['enforce_ip_ranges'])) {
      $sanitary_values['enforce_ip_ranges'] = sanitize_text_field($input['enforce_ip_ranges']);
    }

    return $sanitary_values;
  }

  public function twofa_ip_range_restriction_section_info()
  {
  }

  public function enable_callback()
  {
    printf(
      '<input type="checkbox" name="twofa_ip_range_restriction_option_name[enable]" id="enable" value="enable" %s>',
      (isset($this->twofa_ip_range_restriction_options['enable']) && $this->twofa_ip_range_restriction_options['enable'] === 'enable') ? 'checked' : ''
    );
  }

  public function exempt_ip_ranges_callback()
  {
    printf(
      '<input class="regular-text" type="text" name="twofa_ip_range_restriction_option_name[exempt_ip_ranges]" id="exempt_ip_ranges" value="%s"><p>Exempt 2FA form this IPs</p>',
      isset($this->twofa_ip_range_restriction_options['exempt_ip_ranges']) ? esc_attr($this->twofa_ip_range_restriction_options['exempt_ip_ranges']) : ''
    );
  }
  public function enforce_ip_ranges_callback()
  {
    printf(
      '<input class="regular-text" type="text" name="twofa_ip_range_restriction_option_name[enforce_ip_ranges]" id="enforce_ip_ranges" value="%s"><br /><p>Enforced 2FA form this IPs</p>',
      isset($this->twofa_ip_range_restriction_options['enforce_ip_ranges']) ? esc_attr($this->twofa_ip_range_restriction_options['enforce_ip_ranges']) : ''
    );
  }
}
if (is_admin())
  $twofa_ip_range_restriction = new TwoFAIPRangeRestriction();


function disable_with_ips()
{
  $twofa_ip_range_restriction_options = get_option('twofa_ip_range_restriction_option_name');
  $enable_restriction = $twofa_ip_range_restriction_options['enable'];
  $exempt_ip_ranges = $twofa_ip_range_restriction_options['exempt_ip_ranges'];
  $enforce_ip_ranges = $twofa_ip_range_restriction_options['enforce_ip_ranges'];
  $userAddress = \IPLib\Factory::parseAddressString(getUserIPAddress());

  if ($enable_restriction == 'enable') {

    if ($enforce_ip_ranges) {
      $enforce_ip_ranges = explode(',', $enforce_ip_ranges);
      foreach ($enforce_ip_ranges as $enforce_ip_range) {
        $enforce_ip_range = \IPLib\Factory::parseRangeString(trim($enforce_ip_range));
        if ($enforce_ip_range && $enforce_ip_range->contains($userAddress)) {
          return false;
        }
      }
    }

    if ($exempt_ip_ranges) {
      $ip_ranges = explode(',', $exempt_ip_ranges);
      foreach ($ip_ranges as $ip_range) {
        $ip_range = \IPLib\Factory::parseRangeString(trim($ip_range));
        /*if ($enforce_ip_range && $enforce_ip_range->contains($userAddress)) {
          return false;
        }*/
        if ($ip_range && $ip_range->contains($userAddress)) {
          return true;
        }
      }
    }
  }
  return false;
}

function getUserIPAddress()
{
  //whether ip is from the share internet  
  if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
    $ip = $_SERVER['HTTP_CLIENT_IP'];
  }
  //whether ip is from the proxy  
  elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
  }
  //whether ip is from the remote address  
  else {
    $ip = $_SERVER['REMOTE_ADDR'];
  }
  return $ip;
}
