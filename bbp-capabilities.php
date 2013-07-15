<?php

/**
 * Plugin Name: bbPress Advanced Capabilities
 * Plugin URI:  http://bbpress.org
 * Description: Advanced capabilities editing for bbPress
 * Author:      johnjamesjacoby
 * Author URI:  http://jjj.me
 * Version:     1.0
 * Text Domain: bbpac
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Display user capabilities functionality in bbPress templates
 *
 * @since bbpac 1.0
 */
function bbp_user_edit_capabilities_after_role() {
?>

	<div>
		<label for="bbp-default-caps"><?php _e( 'Reset', 'bbpac' ); ?></label>
		<label>
			<input class="checkbox" type="checkbox" id="bbp-default-caps" name="bbp-default-caps" tabindex="<?php bbp_tab_index(); ?>" />
			<?php _e( 'Reset custom capabilities to match the forum role.', 'bbpac' ); ?>
		</label>
	</div>

	<div>
		<label for=""><?php _e( 'Forum Capabilities', 'bbpac' ); ?></label>

		<fieldset class="bbp-form capabilities">
			<legend><?php _e( 'Forum Capabilities', 'bbpac' ); ?></legend>

			<?php BBP_User_Capabilities::capability_details(); ?>

		</fieldset>
	</div>

<?php
}
add_action( 'bbp_user_edit_after_role', 'bbp_user_edit_capabilities_after_role' );

/**
 * Handle the saving of user capabilities
 *
 * @param int $user_id
 * @return If no user ID passed
 */
function bbp_profile_update_capabilities( $user_id = 0 ) {

	// Bail if no user ID was passed
	if ( empty( $user_id ) )
		return;

	// Either reset caps for role
	if ( ! empty( $_POST['bbp-default-caps'] ) ) {
		bbp_reset_user_caps( $user_id );

	// Or set caps individually
	} else {
		bbp_save_user_caps( $user_id );
	}
}
add_action( 'bbp_profile_update', 'bbp_profile_update_capabilities', 99 );

/**
 * Remove all bbPress capabilities for a given user
 *
 * @since 1.0
 *
 * @param int $user_id
 * @return boolean True on success, false on failure
 */
function bbp_remove_user_caps( $user_id = 0 ) {

	// Bail if no user was passed
	if ( empty( $user_id ) )
		return false;

	// Load up the user
	$user = new WP_User( $user_id );

	// Remove all caps
	foreach ( bbp_get_capability_groups() as $group )
		foreach ( bbp_get_capabilities_for_group( $group ) as $capability )
			$user->remove_cap( $capability );

	// Success
	return true;
}

/**
 * Remove all bbPress capabilities for a given user
 *
 * @since bbPress (r4221)
 *
 * @param int $user_id
 * @return boolean True on success, false on failure
 */
function bbp_reset_user_caps( $user_id = 0 ) {

	// Bail if no user was passed
	if ( empty( $user_id ) )
		return false;

	// Bail if current user cannot edit this user
	if ( ! current_user_can( 'edit_user', $user_id ) )
		return false;

	// Remove all caps for user
	bbp_remove_user_caps( $user_id );

	// Load up the user
	$user = new WP_User( $user_id );

	// User has no role so bail
	if ( ! isset( $user->roles ) )
		return false;

	// Get the user's blog role
	$user_role   = bbp_get_user_blog_role( $user_id );

	// Get the role map
	$role_map    = bbp_get_user_role_map();

	// Use a mapped role
	if ( isset( $role_map[$user_role] ) ) {
		$new_role = $role_map[$user_role];

	// Use the default role
	} else {
		$new_role = bbp_get_default_role();
	}

	// Use first user role
	$caps = bbp_get_caps_for_role( $new_role );

	// Add caps for the first role
	foreach ( $caps as $cap => $value )
		$user->add_cap( $cap, $value );

	// Success
	return true;
}

/**
 * Save all bbPress capabilities for a given user
 *
 * @since bbPress (r4221)
 *
 * @param type $user_id
 * @return boolean
 */
function bbp_save_user_caps( $user_id = 0 ) {

	// Bail if no user was passed
	if ( empty( $user_id ) )
		return false;

	// Bail if current user cannot edit this user
	if ( ! current_user_can( 'edit_user', $user_id ) )
		return false;

	// Load up the user
	$user = new WP_User( $user_id );

	// Loop through capability groups
	foreach ( bbp_get_capability_groups() as $group ) {
		foreach ( bbp_get_capabilities_for_group( $group ) as $capability ) {

			$user->remove_cap( $capability );

			// Add allow/deny capability
			if ( isset( $_POST['_bbp_' . $capability] ) ) {

				// Maybe allow cap
				if ( 'yes' === $_POST['_bbp_' . $capability] ) {
					$user->add_cap( $capability, true );

				// Maybe explicitly deny cap
				} elseif ( 'no' === $_POST['_bbp_' . $capability] ) {
					$user->add_cap( $capability, false );
				}
			}
		}
	}

	// Success
	return true;
}

/** Helpers *******************************************************************/

/**
 * Return the capability groups
 *
 * @since bbPress (r4163)
 *
 * @return array of groups
 */
function bbp_get_capability_groups() {
	return apply_filters( 'bbp_get_capability_groups', array(
		'primary',
		'forums',
		'topics',
		'replies',
		'topic_tags'
	) );
}

/**
 * Return capabilities for the group
 *
 * @since bbPress (r4163)
 *
 * @param string $group
 * @return array of capabilities
 */
function bbp_get_capabilities_for_group( $group = '' ) {
	switch ( $group ) {
		case 'primary'    :
			return bbp_get_primary_capabilities();
			break;
		case 'forums'     :
			return bbp_get_forums_capabilities();
			break;
		case 'topics'     :
			return bbp_get_topics_capabilities();
			break;
		case 'replies'    :
			return bbp_get_replies_capabilities();
			break;
		case 'topic_tags' :
			return bbp_get_topic_tags_capabilities();
			break;
		default :
			return array();
			break;
	}
}

/**
 * Output the human readable capability group title
 *
 * @since bbPress (r4163)
 *
 * @param string $group
 * @uses bbp_get_capability_group_title()
 */
function bbp_capability_group_title( $group = '' ) {
	echo bbp_get_capability_group_title( $group );
}
	/**
	 * Return the human readable capability group title
	 *
	 * @since bbPress (r4163)
	 *
	 * @param string $group
	 * @return string
	 */
	function bbp_get_capability_group_title( $group = '' ) {

		// Default return value to capability group
		$retval = $group;

		switch( $group ) {
			case 'primary' :
				$retval = __( 'Primary capabilities', 'bbpac' );
				break;
			case 'forums' :
				$retval = __( 'Forum capabilities', 'bbpac' );
				break;
			case 'topics' :
				$retval = __( 'Topic capabilites', 'bbpac' );
				break;
			case 'topic_tags' :
				$retval = __( 'Topic tag capabilities', 'bbpac' );
				break;
			case 'replies' :
				$retval = __( 'Reply capabilities', 'bbpac' );
				break;
		}

		return apply_filters( 'bbp_get_capability_group_title', $retval, $group );
	}

/**
 * Get the primary bbPress capabilities
 *
 * @since bbPress (r4163)
 *
 * @return array of primary capabilities
 */
function bbp_get_primary_capabilities() {
	return apply_filters( 'bbp_get_primary_capabilities', array(
		'spectate',
		'participate',
		'moderate',
		'throttle',
		'view_trash',
	) );
}

/**
 * Return forum post-type capabilities, used when registering the post type
 *
 * @since bbPress (r4163)
 *
 * @return array of forums capabilities
 */
function bbp_get_forums_capabilities() {
	return apply_filters( 'bbp_get_forums_capabilities', array(
		'publish_forums',
		'edit_forums',
		'edit_others_forums',
		'delete_forums',
		'delete_others_forums',
		'read_private_forums',
		'read_hidden_forums'
	) );
}

/**
 * Return topic post-type capabilities, used when registering the post type
 *
 * @since bbPress (r4163)
 *
 * @return array of topics capabilities
 */
function bbp_get_topics_capabilities() {
	return apply_filters( 'bbp_get_topics_capabilities', array(
		'publish_topics',
		'edit_topics',
		'edit_others_topics',
		'delete_topics',
		'delete_others_topics',
		'read_private_topics'
	) );
}

/**
 * Get the reply post-type capabilities
 *
 * @since bbPress (r4163)
 *
 * @return array of replies capabilities
 */
function bbp_get_replies_capabilities() {
	return apply_filters( 'bbp_get_replies_capabilities', array(
		'publish_replies',
		'edit_replies',
		'edit_others_replies',
		'delete_replies',
		'delete_others_replies',
		'read_private_replies'
	) );
}

/**
 * Return topic-tag taxonomy capabilities, used when registering the taxonomy
 *
 * @since bbPress (r4163)
 *
 * @return array of topic-tag capabilities
 */
function bbp_get_topic_tags_capabilities() {
	return apply_filters( 'bbp_get_topic_tags_capabilities', array(
		'manage_topic_tags',
		'edit_topic_tags',
		'delete_topic_tags',
		'assign_topic_tags'
	) );
}

/**
 * Output the human readable capability title
 *
 * @since bbPress (r4163)
 *
 * @param string $group
 * @uses bbp_get_capability_title()
 */
function bbp_capability_title( $capability = '' ) {
	echo bbp_get_capability_title( $capability );
}
	/**
	 * Return the human readable capability title
	 *
	 * @since bbPress (r4163)
	 *
	 * @param string $capability
	 * @return string
	 */
	function bbp_get_capability_title( $capability = '' ) {

		// Default return value to capability
		$retval = $capability;

		switch( $capability ) {

			// Primary
			case 'spectate' :
				$retval = __( 'Spectate forum discussion', 'bbpac' );
				break;
			case 'participate' :
				$retval = __( 'Participate in forums', 'bbpac' );
				break;
			case 'moderate' :
				$retval = __( 'Moderate entire forum', 'bbpac' );
				break;
			case 'throttle' :
				$retval = __( 'Skip forum throttle check', 'bbpac' );
				break;
			case 'view_trash' :
				$retval = __( 'View items in forum trash', 'bbpac' );
				break;

			// Forum caps
			case 'read_forum' :
				$retval = __( 'View forum', 'bbpac' );
				break;
			case 'edit_forum' :
				$retval = __( 'Edit forum', 'bbpac' );
				break;
			case 'trash_forum' :
				$retval = __( 'Trash forum', 'bbpac' );
				break;
			case 'delete_forum' :
				$retval = __( 'Delete forum', 'bbpac' );
				break;
			case 'moderate_forum' :
				$retval = __( 'Moderate forum', 'bbpac' );
				break;
			case 'publish_forums' :
				$retval = __( 'Create forums', 'bbpac' );
				break;
			case 'edit_forums' :
				$retval = __( 'Edit their own forums', 'bbpac' );
				break;
			case 'edit_others_forums' :
				$retval = __( 'Edit all forums', 'bbpac' );
				break;
			case 'delete_forums' :
				$retval = __( 'Delete their own forums', 'bbpac' );
				break;
			case 'delete_others_forums' :
				$retval = __( 'Delete all forums', 'bbpac' );
				break;
			case 'read_private_forums' :
				$retval = __( 'View private forums', 'bbpac' );
				break;
			case 'read_hidden_forums' :
				$retval = __( 'View hidden forums', 'bbpac' );
				break;

			// Topic caps
			case 'read_topic' :
				$retval = __( 'View topic', 'bbpac' );
				break;
			case 'edit_topic' :
				$retval = __( 'Edit topic', 'bbpac' );
				break;
			case 'trash_topic' :
				$retval = __( 'Trash topic', 'bbpac' );
				break;
			case 'moderate_topic' :
				$retval = __( 'Moderate topic', 'bbpac' );
				break;
			case 'delete_topic' :
				$retval = __( 'Delete topic', 'bbpac' );
				break;
			case 'publish_topics' :
				$retval = __( 'Create topics', 'bbpac' );
				break;
			case 'edit_topics' :
				$retval = __( 'Edit their own topics', 'bbpac' );
				break;
			case 'edit_others_topics' :
				$retval = __( 'Edit others topics', 'bbpac' );
				break;
			case 'delete_topics' :
				$retval = __( 'Delete own topics', 'bbpac' );
				break;
			case 'delete_others_topics' :
				$retval = __( 'Delete others topics', 'bbpac' );
				break;
			case 'read_private_topics' :
				$retval = __( 'View private topics', 'bbpac' );
				break;

			// Reply caps
			case 'read_reply' :
				$retval = __( 'Read reply', 'bbpac' );
				break;
			case 'edit_reply' :
				$retval = __( 'Edit reply', 'bbpac' );
				break;
			case 'trash_reply' :
				$retval = __( 'Trash reply', 'bbpac' );
				break;
			case 'delete_reply' :
				$retval = __( 'Delete reply', 'bbpac' );
				break;
			case 'publish_replies' :
				$retval = __( 'Create replies', 'bbpac' );
				break;
			case 'edit_replies' :
				$retval = __( 'Edit own replies', 'bbpac' );
				break;
			case 'edit_others_replies' :
				$retval = __( 'Edit others replies', 'bbpac' );
				break;
			case 'delete_replies' :
				$retval = __( 'Delete own replies', 'bbpac' );
				break;
			case 'delete_others_replies' :
				$retval = __( 'Delete others replies', 'bbpac' );
				break;
			case 'read_private_replies' :
				$retval = __( 'View private replies', 'bbpac' );
				break;

			// Topic tag caps
			case 'manage_topic_tags' :
				$retval = __( 'Remove tags from topics', 'bbpac' );
				break;
			case 'edit_topic_tags' :
				$retval = __( 'Edit topic tags', 'bbpac' );
				break;
			case 'delete_topic_tags' :
				$retval = __( 'Delete topic tags', 'bbpac' );
				break;
			case 'assign_topic_tags' :
				$retval = __( 'Assign tags to topics', 'bbpac' );
				break;
		}

		return apply_filters( 'bbp_get_capability_title', $retval, $capability );
	}

/**
/**
 * Loads bbPress users admin area
 *
 * @package bbPress
 * @subpackage Administration
 * @since bbPress (r2464)
 */
class BBP_User_Capabilities {

	/**
	 * The bbPress users admin loader
	 *
	 * @since bbPress (r2515)
	 *
	 * @uses BBP_Users_Admin::setup_globals() Setup the globals needed
	 * @uses BBP_Users_Admin::setup_actions() Setup the hooks and actions
	 */
	public function __construct() {
		$this->setup_actions();
	}

	/**
	 * Setup the admin hooks, actions and filters
	 *
	 * @since bbPress (r2646)
	 * @access private
	 *
	 * @uses add_action() To add various actions
	 */
	public function setup_actions() {

		// Add table CSS to wp_head, and conditionally skip it there
		add_action( 'wp_head',        array( $this, 'table_css'  ) );

		// Admin actions
		add_action( 'load-user-edit.php', array( $this, 'load_users' ) );
	}

	public function load_users() {

		// Bail if network admin
		if ( is_network_admin() )
			return;

		// Admin styles
		add_action( 'admin_head',        array( $this, 'table_css' ) );

		// User profile edit/display actions
		add_action( 'edit_user_profile', array( $this, 'advanced_capability_display' ), 20 );

		// Noop WordPress additional caps output area
		add_filter( 'additional_capabilities_display', '__return_false' );
	}

	/**
	 * Add some general styling to the admin area
	 *
	 * @since bbPress (r2464)
	 *
	 * @uses bbp_get_forum_post_type() To get the forum post type
	 * @uses bbp_get_topic_post_type() To get the topic post type
	 * @uses bbp_get_reply_post_type() To get the reply post type
	 * @uses sanitize_html_class() To sanitize the classes
	 */
	public function table_css() {

		// Non admin action
		if ( bbp_is_single_user_edit() || is_admin() ) : ?>

		<style type="text/css" media="screen">
		/*<![CDATA[*/
			table.bbp-user-access-control {
				border: 1px solid #ddd;
				border-collapse: collapse;
				margin-bottom: 0;
			}

			table.bbp-user-access-control th,
			table.bbp-user-access-control td {
				padding: 6px 8px;
				vertical-align: middle;
			}

			table.bbp-user-access-control tfoot th,
			table.bbp-user-access-control thead th {
				background-color: #e6e6e6;
			}

			table.bbp-user-access-control tfoot th {
				text-align: right;
			}

			table.bbp-user-access-control thead th {
				font-weight: bold;
				text-align: center;
			}

			table.bbp-user-access-control tbody th {
				background-color: #f1f1f1;
				border-top: 1px solid #ddd;
				border-bottom: 1px solid #ddd;
			}

			table.bbp-user-access-control tbody tr:nth-child(odd) {
				background-color: #f9f9f9;
			}

			table.bbp-user-access-control tbody td {
				text-align: center;
			}

			table.bbp-user-access-control tbody td.allowed {
				color: seagreen;
			}

			table.bbp-user-access-control tbody td.denied {
				color: crimson;
			}

			table.bbp-user-access-control td.bbp-capability-key {
				width: auto;
				text-align: center;
				font-family: monospace;
			}

			table.bbp-user-access-control th.bbp-capability-description {
				background-color: #f5f5f5;
				width: 30%;
				text-align: right;
				border: none;
			}

			table.bbp-user-access-control tr.changed {
				border: 2px solid #ccc;
			}

			table.bbp-user-access-control tr.changed th,
			table.bbp-user-access-control tr.changed td {
				background-color: #ffd;
			}

		/*]]>*/
		</style>

		<?php endif;
	}

	/**
	 * Responsible for displaying bbPress's advanced capability interface.
	 *
	 * Hidden by default. Must be explicitly enabled.
	 *
	 * @since bbPress (r2464)
	 *
	 * @param WP_User $profileuser User data
	 * @uses do_action() Calls 'bbp_user_profile_forums'
	 * @return bool Always false
	 */
	public static function advanced_capability_display( $profileuser = false ) {
	?>

		<table class="form-table">
			<tbody>
				<tr>
					<th><?php _e( 'Capability Details:', 'bbpac' ); ?></th>
					<td>
						<?php BBP_User_Capabilities::capability_details( $profileuser ); ?>
					</td>
				</tr>
			</tbody>
		</table>

	<?php
	}

	/**
	 *
	 * @param type $profileuser
	 * @return type
	 */
	public static function capability_details( $profileuser = false ) {

		// Get the displayed user data
		if ( ( false === $profileuser ) && bbp_is_single_user_edit() ) {
			$profileuser = bbpress()->displayed_user;
		} elseif ( ! is_admin() ) {
			return;
		}

		// Bail if current user cannot edit users
		if ( ! current_user_can( 'edit_user', $profileuser->ID ) ) {
			return;
		}

		// Get the capabilities for the user's forum role
		$role_caps = bbp_get_caps_for_role( bbp_get_user_role( $profileuser->ID ) ); ?>

		<table class="bbp-user-access-control">
			<thead>
				<tr>
					<th><?php _e( 'Description', 'bbpac' ); ?></th>
					<th><?php _e( 'Permission',  'bbpac' ); ?></th>
					<th><?php _e( 'Source',      'bbpac' ); ?></th>
					<th class="bbp-capability-key"><?php _e( 'Key',       'bbpac' ); ?></th>
					<th class="bbp-capability-action"><?php _e( 'Action', 'bbpac' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( bbp_get_capability_groups() as $group ) : ?>

					<tr class="bbp-user-capabilities">
						<th colspan="5"><?php bbp_capability_group_title( $group ); ?></th>
					</tr>

					<?php foreach ( bbp_get_capabilities_for_group( $group ) as $capability ) :

						// Is user denied this capability?
						if ( ! isset( $profileuser->allcaps[$capability] ) || ( false === $profileuser->allcaps[$capability] ) ) {
							$user_has = __( 'Denied', 'bbpac' );
							$class    = 'denied';

							// Does the user not have this cap because of the role or user?
							if ( ! in_array( $capability, array_keys( $role_caps ) ) ) {
								$inherited       = __( 'Role', 'bbpac' );
								$inherited_class = 'role';
							} else {
								$inherited       = __( 'User', 'bbpac' );
								$inherited_class = 'user';
							}

						// Is user allowed this capability?
						} elseif ( isset( $profileuser->allcaps[$capability] ) && ( true === $profileuser->allcaps[$capability] ) ) {
							$user_has = __( 'Allowed', 'bbpac' );
							$class    = 'allowed';

							// Does the user have this cap because of the role or user?
							if ( in_array( $capability, array_keys( $role_caps ) ) ) {
								$inherited       = __( 'Role', 'bbpac' );
								$inherited_class = 'role';
							} else {
								$inherited       = __( 'User', 'bbpac' );
								$inherited_class = 'user';
							}
						}

						// Is the cap changed from the default role?
						$changed_allow = ( $class === 'allowed' ) && ( $inherited_class === 'user' );
						$changed_deny  = ( $class === 'denied'  ) && ( $inherited_class === 'user' );

						// Allow the table row to be highlighted
						if ( true === $changed_allow || true === $changed_deny ) {
							$row_class = 'changed';
						} else {
							$row_class = 'not-changed';
						} ?>

						<tr class="<?php echo esc_attr( $row_class ); ?>">
							<th class="bbp-capability-description"><?php bbp_capability_title( $capability ); ?></th>
							<td class="<?php echo esc_attr( $class           ); ?>"><?php echo esc_html( $user_has  ); ?></td>
							<td class="<?php echo esc_html( $inherited_class ); ?>"><?php echo esc_html( $inherited ); ?></td>
							<td class="bbp-capability-key"><?php echo esc_html( $capability ); ?></td>
							<td class="bbp-capability-action">
								<select id="_bbp_<?php echo esc_attr( $capability ); ?>" name="_bbp_<?php echo esc_attr( $capability ); ?>" tabindex="<?php bbp_tab_index(); ?>">
									<option value=""><?php _e( 'Default', 'bbpac' ); ?></option>
									<?php if ( ( $class === 'denied' && 'role' === $inherited_class ) || ( $changed_allow ) ) : ?>
										<option value="yes" <?php selected( $changed_allow ); ?>><?php _e( 'Allow', 'bbpac' ); ?></option>
									<?php elseif ( ( $class === 'allowed' && 'role' === $inherited_class ) || ( $changed_deny ) ) : ?>
										<option value="no"  <?php selected( $changed_deny  ); ?>><?php _e( 'Deny',  'bbpac' ); ?></option>
									<?php endif; ?>
								</select>
							</td>
						</tr>

					<?php endforeach;
				endforeach; ?>
			</tbody>
			<?php if ( is_admin() ) : ?>
			<tfoot>
				<tr class="bbp-default-caps-wrapper">
					<th colspan="5">
						<input type="submit" name="bbp-default-caps" class="button" value="<?php esc_attr_e( 'Reset to Default', 'bbpac' ); ?>"/>
					</th>
				</tr>
			</tfoot>
			<?php endif; ?>
		</table>

	<?php
	}
}
new BBP_User_Capabilities();
