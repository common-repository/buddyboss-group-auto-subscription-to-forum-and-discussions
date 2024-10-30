<?php
 /**
 * Setting page for plugin dashboard
 * @package    buddyboss-group-auto-subscription-to-forum-and-discussions
 * @subpackage admin
 */

if(!class_exists('BBSGS_Setting')):

class BBSGS_Setting {

  protected static $instance;

  public function __construct() {
    add_action('admin_init', array($this, 'register_settings'));
    add_action('admin_menu', array($this, 'register_options_page'));
    add_action('admin_head', array($this, 'settings_style'));
  }

  /**
   * Add option value
   */
  public function register_settings() {
    add_option('bbsgs_option_discussion_types');
    register_setting(
      'bbsgs_options_group', 'bbsgs_option_discussion_types', 'bbsgs_callback'
    );
  }

  /**
   * Create admin menu page
   */
  public function register_options_page() {
    add_options_page(
      'Buddyboss Group Subscription', 'Group Subscription Menu', 
      'manage_options', 'bbsgs', array($this, 'bbsgs_options_page')
    );
  }

  public function settings_style() {
    echo '<style>
      #bbsgs-settings input[type="text"] {
        width: 300px;
      }
    </style>';
  }

  public function bbsgs_options_page() { ?>
    <div id="bbsgs-settings">
      <h2>Buddyboss Group Subscription Settings</h2>
      <form method="post" action="options.php">
        <?php settings_fields('bbsgs_options_group'); ?>
        <table>
          <tr valign="top">
            <th scope="row">
              <label for="bbsgs_option_discussion_types">Discussion types supported (comma-separated strings): </label>
            </th>
            <td>
              <input 
                style=""
                type="text" 
                id="bbsgs_option_discussion_types" 
                name="bbsgs_option_discussion_types" 
                value="<?php echo get_option('bbsgs_option_discussion_types'); ?>" 
                placeholder="Default: publish,private,inherit,closed"
              />
            </td>
          </tr>
          <tr valign="top">
            <th scope="row">&nbsp</th>
            <td>
              <label>User within group will be only subcribe to selected discussion type/s.</label>
            </td>
          </tr>
        </table>
        <?php submit_button(); ?>
      </form>
    </div>
  <?php
  }

  public static function get_instance() {
    if(!isset(self::$instance)) {
      self::$instance = new self();
    }
    return self::$instance;
  }

}

BBSGS_Setting::get_instance();

endif;

?>