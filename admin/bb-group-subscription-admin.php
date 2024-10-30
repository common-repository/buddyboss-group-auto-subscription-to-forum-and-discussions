<?php
 /**
 * Admin hooks responsible for user group subscription
 * @package    buddyboss-group-auto-subscription-to-forum-and-discussions
 * @subpackage admin
 */

if(!class_exists('BBSGS_Admin')):

class BBSGS_Admin {

  protected static $instance;

  public function __construct() {
    add_action('groups_join_group', array($this, 'join_group'), 10, 2);
    add_action('groups_membership_accepted', array($this, 'groups_membership_accepted'), 10, 2);
    add_action('groups_leave_group', array($this, 'leave_group'), 10, 2);
    add_action('bbp_new_topic', array($this, 'subs_all_users_to_new_topic'), 20, 4); 
  }

  /**
   * Add member who joins a group to its forum
   */
  public function join_group($group_id, $user_id) {
    if(!$user_id) return false;

    $forum_ids = bbp_get_group_forum_ids($group_id);

    if(!empty($forum_ids)) {
      $forum_id = array_shift($forum_ids);
    }

    $forum_response = $this->subscribe_user_to_forum($user_id, $forum_id);
    if($forum_response):

      $topics_response = $this->subscribe_user_to_all_discussions($user_id, $forum_id);
      if($topics_response == false):
        error_log('Unable to subcribe user '. $user_id . ' to all topics under '. $forum_id);
      endif;

    else:
      error_log('Unable to subcribe user '. $user_id . ' to forum '. $forum_id);
    endif;
  }

  /**
   * Add member who accepted to its forum
   */
  public function groups_membership_accepted($user_id, $group_id) {
    $this->join_group($group_id, $user_id);
  }

  /**
   * Add member who joins a group to its forum
   */
  function leave_group($group_id, $user_id) {
    if(!$user_id) return false;

    $forum_ids = bbp_get_group_forum_ids($group_id);

    if(!empty($forum_ids)) {
      $forum_id = array_shift($forum_ids);
    }

    $forum_response = $this->leave_user_to_forum($user_id, $forum_id);
    if($forum_response) {
      delete_user_option($user_id, '_bbp_subscriptions');
    } else {
      error_log('Unable to leave user '. $user_id . ' from forum '. $forum_id);
    }
  }

  /**
   * Register all users to ne topic under Social Group
   */
  public function subs_all_users_to_new_topic($topic_id, $forum_id, $anonymous_data, $topic_author) {
    if($topic_author):
      if(bbp_is_user_subscribed_to_forum($topic_author, $forum_id) == false):
        $this->subscribe_user_to_forum($topic_author, $forum_id);
      endif;
      bbp_add_user_topic_subscription($topic_author, $topic_id);
    endif;

    if($group_id = bp_get_current_group_id()) {
      $this->check_member_subscription($group_id, $forum_id, $topic_id);
    }
    
    $post = get_post($topic_id);
    if($post):
      $bbsgs_option_discussion_types = trim(get_option('bbsgs_option_discussion_types'));
      if(!empty($bbsgs_option_discussion_types)):
        if(!(strpos($bbsgs_option_discussion_types, $post->post_status) !== false)):
          error_log('Discussion type '.$post->post_status.'is excluded from subscription.');
          return false;
        endif;
      endif;
    endif;

    $users = $this->get_bp_row(
      'usermeta', 
      'GROUP_CONCAT(user_id) as ids', 
      'WHERE `meta_key` LIKE "%_bbp_forum_subscriptions" AND FIND_IN_SET('.$forum_id.', `meta_value`) GROUP BY "all"'
    );
    
    if(!empty($users)):
      $user_ids = explode(',', $users->ids);
      foreach ($user_ids as $user_id):
        if($topic_author == $user_id) continue;
        bbp_add_user_topic_subscription($user_id, $topic_id);
      endforeach;
    else:
      error_log('No users are subscribe to forum '.$forum_id);
    endif;
  }

  /**
   * Subscribe user to forum
   * @param integer $user_id
   * @param integer $forum_id
   */
  private function subscribe_user_to_forum($user_id, $forum_id) {
    return bbp_add_user_forum_subscription($user_id, $forum_id);
  }

  /**
   * Unsubscribe user to forum
   * @param integer $user_id
   * @param integer $forum_id
   */
  private function leave_user_to_forum($user_id, $forum_id) {
    return bbp_remove_user_forum_subscription($user_id, $forum_id);
  }

  /**
   * Subscribe user to all discussions in the Forum
   * @param integer $user_id
   * @param integer $forum_id
   */
  private function subscribe_user_to_all_discussions($user_id, $forum_id) {
    $defaul_post_status = '"publish","private","inherit","closed"';

    $bbsgs_option_discussion_types = trim(get_option('bbsgs_option_discussion_types'));

    if(!empty($bbsgs_option_discussion_types)) {
      $defaul_post_status = $this->parse_bbsgs_option_discussion_types($bbsgs_option_discussion_types);
    }

    $topics = $this->get_bp_row(
      'posts', 
      'GROUP_CONCAT(ID) as ids', 
      'WHERE `post_status` in ('.$defaul_post_status.') AND `post_parent` = '.$forum_id.' GROUP BY "all"'
    );

    if(!empty($topics)):

      update_user_option($user_id, '_bbp_subscriptions', $topics->ids);

      $topics_arr_ids = explode(',', $topics->ids);
      foreach ($topics_arr_ids as $topic_id) {
        wp_cache_delete('bbp_get_topic_subscribers_' . $topic_id, 'bbpress_users');
      }
      return true;

    else:
      error_log('Forum '.$forum_id.' does not have any topics');
      return false;
    endif;
  }

  /**
   * Parse bbsgs_option_discussion_types to SQL friendly query
   */
  private function parse_bbsgs_option_discussion_types($bbsgs_option_discussion_types) {
    $discussions = explode(',', $bbsgs_option_discussion_types);
    $bbsgs_option_discussion_types = '';

    foreach ($discussions as $discussion) {
      $bbsgs_option_discussion_types .= '"'.$discussion.'",';
    }

    return rtrim($bbsgs_option_discussion_types, ',');
  }

  /**
   * Check if user subscribe to main discussion
   */
  private function check_member_subscription($group_id, $forum_id, $topic_id) {
    $member_args = array(
      'group_id' => $group_id,
      'per_page' => -1,
      'exclude_admins_mods' => 0
    );

    $members = groups_get_group_members($member_args);

    if(!isset($members['members'])) return;

    if(count($members['members']) > 0):
      foreach ($members['members'] as $member):
        if(bbp_is_user_subscribed_to_forum($member->ID, $forum_id) == false):
          $this->subscribe_user_to_forum($member->ID, $forum_id);
        endif;
      endforeach;
    endif;
  }

  /**
   * Generic row query for BBSGS
   * @param string $table
   * @param string $selector
   * @param string $q
   */
  private function get_bp_row($table, $selector, $q) {
    global $wpdb;
    $table = $wpdb->prefix . $table;
    $bp_row = $wpdb->get_row( "SELECT $selector FROM $table $q" );
    return $bp_row;
  }

  public static function get_instance() {
    if(!isset(self::$instance)) {
      self::$instance = new self();
    }
    return self::$instance;
  }

}

BBSGS_Admin::get_instance();
  
endif;