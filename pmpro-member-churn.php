<?php
/*
Plugin Name: Paid Memberships Pro - Member Churn Add On
Plugin URI: https://aaron-colon.com
Description: Display membership churn.
Version: 0.1
Author: Aaron Colón
Author URI: https://aaron-colon.com
*/

/*
 * Add a Custom Report to the Memberships > Reports Screen in Paid Memberships Pro.
 *
 * For each report, add a line like:
 * global $pmpro_reports;
 * $pmpro_reports['slug'] = 'Title';
 *
 * For each report, also write two functions:
 * pmpro_report_{slug}_widget()   to show up on the report homepage.
 * pmpro_report_{slug}_page()     to show up when users click on the report page widget.
 *
 */

global $pmpro_reports;
$pmpro_reports[ 'member_churn' ] = __( 'Member Churn Report', 'pmpro-member-churn');

/**
 * Init
 */
function pmpro_report_member_churn_init() {
  if (! pmpro_report_member_churn_can_view()) {
    // remove report widget
    unset($GLOBALS['pmpro_reports']['member_churn']);
    return;
  }
}
add_action( 'plugins_loaded', 'pmpro_report_member_churn_init' );

/**
 * Check if the current user can view the report
 */
function pmpro_report_member_churn_can_view() {
  $current_user = wp_get_current_user();

  $users = array(
    'user@domain.com';
  );

  if ( ! in_array( strtolower( $current_user->user_email ), $users, true ) ) {
  	return false;
  }

  return true;
}

/**
 * Export CSV of member churn report
 * Hooks into admin_init preventing php://output population
 */
function pmpro_report_member_churn_export() {
  if (isset($_REQUEST['export']) && (isset($_REQUEST['report']) && $_REQUEST['report'] == 'member_churn') && pmpro_report_member_churn_can_view()) {

    $column_headers = array('Level', 'Min', 'Avg', 'Max', 'Count');
    $member_churn_export = pmpro_format_member_churn(pmpro_calc_member_churn(pmpro_get_member_churn_data()));
    $filename = 'refit_member_churn_'. date('Y-m-d') .'.csv';
    $separator = ',';

    if (empty($member_churn_export)) {
      return;
    }

    header('Content-Type: application/csv');
    header('Content-Disposition: attachment; filename="'. $filename .'";');
    header('Pragma: no-cache');
    header('Expires: 0');

    $f = fopen('php://output', 'w');

    if (! empty($column_headers)) {
      fputcsv($f, $column_headers, $separator);
    }

    foreach ($member_churn_export as $line) {
      fputcsv($f, $line, $separator);
    }

    fclose($f);

    exit;
  }
}
add_action( 'admin_init', 'pmpro_report_member_churn_export' );

/**
 * Display a Member Churn report widget on the Memberships > Reports page
 */
function pmpro_report_member_churn_widget() {
  if ( function_exists( 'pmpro_report_member_churn_page' ) ) { ?>
  <div id="pmpro_report_member_churn" class="pmpro_report-holder">
    <p class="pmpro_report-button">
      <a class="button button-primary" href="<?php echo admin_url( 'admin.php?page=pmpro-reports&report=member_churn' ); ?>"><?php _e('View Report', 'paid-memberships-pro' );?></a>
    </p>
  </div>
  <?php
  }
}

/**
 * Display a custom report for Member Churn
 */
function pmpro_report_member_churn_page() { ?>
  <?php
  if (! pmpro_report_member_churn_can_view()) {
    wp_safe_redirect(admin_url('admin.php?page=pmpro-reports'));
    exit;
    return;
  }
  ?>

  <h2><?php _e( 'Member Churn Report', 'pmpro-member-churn' ); ?></h2>

  <?php
  if (empty($member_churn_data)) {
    $member_churn_data = pmpro_get_member_churn_data();
  }

  if ( empty($member_churn_data) ) {
    esc_html_e( '<p>No paying members found.</p>', 'pmpro-member-churn' );
  } else {
    ?>
    <p class="pmpro_report-button">
      <a class="button button-primary" href="<?php echo admin_url( 'admin.php?page=pmpro-reports&report=member_churn&flushcache=1' ); ?>"><?php _e('Flush Churn Report Cache', 'paid-memberships-pro' );?></a>

      <a class="button button-secondary" href="<?php echo admin_url( 'admin.php?page=pmpro-reports&report=member_churn&raw=1' ); ?>"><?php _e('View Raw Data', 'paid-memberships-pro' );?></a>

      <a class="button button-secondary" href="<?php echo admin_url( 'admin.php?page=pmpro-reports&report=member_churn&export=1' ); ?>"><?php _e('Download CSV', 'paid-memberships-pro' );?></a>
    </p>
    <table class="widefat striped">
      <thead>
        <tr class="thead">
					<th><?php esc_html_e( 'Level', 'pmpro-member-churn' ); ?></th>
					<th><?php esc_html_e( 'Min', 'pmpro-member-churn' ); ?></th>
					<th><?php esc_html_e( 'Avg', 'pmpro-member-churn' ); ?></th>
          <th><?php esc_html_e( 'Max', 'pmpro-member-churn' ); ?></th>
          <th><?php esc_html_e( 'Count', 'pmpro-member-churn' ); ?></th>
        </tr>
      </thead>
      <tbody>
      <?php
        $member_churn = pmpro_calc_member_churn($member_churn_data);
        $member_churn_f = pmpro_format_member_churn($member_churn);

        for ($i = 0; $i < count($member_churn_f); $i++) {
          $level = $member_churn_f[$i]['name'];
          $min   = $member_churn_f[$i]['min'];
          $avg   = $member_churn_f[$i]['avg'];
          $max   = $member_churn_f[$i]['max'];
          $count = $member_churn_f[$i]['count'];
      ?>
        <tr>
          <td>
            <?php if ( ! empty( $level ) ) { echo $level; } else { _e( 'N/A', 'pmpro-member-history' ); } ?>
          </td>
          <td>
            <?php echo sprintf( _n('%s day', '%s days', $min, 'pmpro-member-churn'), $min ); ?>
          </td>
          <td>
            <?php echo sprintf( _n('%s day', '%s days', $avg, 'pmpro-member-churn'), $avg ); ?>
          </td>
          <td>
            <?php echo sprintf( _n('%s day', '%s days', $max, 'pmpro-member-churn'), $max ); ?>
          </td>
          <td>
            <?php echo $count; ?>
          </td>
        </tr>
      <?php
        }
      ?>
      </tbody>
    </table>

    <?php
    if ( isset( $_REQUEST['raw'] ) ) {
      echo '<pre>';
        var_dump($member_churn);
      echo '</pre>';
    }
  }
}

function pmpro_get_member_churn_data() {
  global $wpdb;
  $member_churn_data;
  $membership_ids = '6, 7, 12, 13, 16, 17';

  if ( isset( $_REQUEST['flushcache']) ) {
    if (delete_transient( 'pmpro_member_churn' )) {
      echo '<p>Report cache clear success.</p>';
    } else {
      echo '<p>Report cache clear failed or does not exist.</p>';
    }
  }

  $member_churn_data = get_transient( 'pmpro_member_churn' );

  if ( $member_churn_data === false ) {
    $sqlQuery = "
    SELECT user_id, membership_id, status, startdate, enddate
      FROM $wpdb->pmpro_memberships_users
			WHERE membership_id > 0
				AND status NOT IN('token','review','pending','error','refunded')
        AND membership_id IN($membership_ids)
      ORDER BY user_id DESC, startdate ASC
      ";
    $member_churn_data = $wpdb->get_results( $sqlQuery );

    set_transient( 'pmpro_member_churn', $member_churn_data, 4 * HOUR_IN_SECONDS );
  }

  return $member_churn_data;
}

/**
 * Calculate member churn
 *
 * @param {Array} $data an array of objects
 * @return {Array}
 */
function pmpro_calc_member_churn($data) {
  $res_temp = array();
  $res_final = array();

  for ($i = 0; $i < count($data); $i++) {
    $level = pmpro_getLevel($data[$i]->membership_id);
    $level_name = $level->name;

    // determine if this is a level upgrade/downgrade
    // compare curr item and next item
    if ( ($i < count($data) - 1) && pmpro_is_level_change($data[$i], $data[$i + 1]) ) {
      $level_name_curr = pmpro_getLevel($data[$i]->membership_id)->name;
      $level_name_next = pmpro_getLevel($data[$i + 1]->membership_id)->name;
      $level_name = $level_name_curr . ' > ' . $level_name_next;
    }
    else if ( ($i < count($data) - 1) && pmpro_is_returning_customer($data[$i], $data[$i + 1]) ) {
      $level_name = $level_name . ' (Returning Customer < 30 days)';
    }

    // calculate membership length
    $res_temp[$level_name][] = pmpro_calc_membership_length($data[$i]->startdate, $data[$i]->enddate);
  }

  // sort values low to high
  foreach ($res_temp as &$res) {
    sort($res);
  }
  unset($res);

  // calc final stats
  foreach ($res_temp as $k => $v) {
    $res_final[] = array(
      'name'  => $k,
      'min'   => $v[0],
      'avg'   => pmpro_calc_membership_length_avg($v),
      'max'   => (count($v) > 1) ? $v[count($v) - 1] : $v[0],
      'count' => count($v),
    );
  }

  pmpro_array_sort_by_column($res_final, 'name');

  return $res_final;
}

function pmpro_format_member_churn($data) {
  $data_f = array();

  for ($i = 0; $i < count($data); $i++) {
    $data_f[] = array(
      'name'  => $data[$i]['name'],
      'min'   => pmpro_seconds_to_days($data[$i]['min']),
      'avg'   => pmpro_seconds_to_days($data[$i]['avg']),
      'max'   => pmpro_seconds_to_days($data[$i]['max']),
      'count' => esc_html($data[$i]['count']),
    );
  }

  return $data_f;
}

/**
 * Calculate the length of a membership
 *
 * @param {String} $startDate
 * @param {String} $endDate
 * @return {String|null}
 */
function pmpro_calc_membership_length($startDate, $endDate) {
  $start = strtotime($startDate);
  $end = ($endDate == null || $endDate == '0000-00-00 00:00:00') ? time() : strtotime($endDate);
  return ($start && $end) ? $end - strtotime($startDate) : null;
}

/**
 * Calculate the average length of a membership
 *
 * @param {Array} $values
 * @return {Int}
 */
function pmpro_calc_membership_length_avg($values) {
  if (! count($values) > 1) {
    return $values[0];
  }

  $total = 0;
  for ($i = 0; $i < count($values); $i++) {
    $total += $values[$i];
  }
  return ceil($total / count($values));
}

/**
 * Convert seconds to days
 *
 * @param {Int} $seconds
 * @return {Int}
 */
function pmpro_seconds_to_days($seconds) {
  return ceil($seconds / 60 / 60 / 24);
}

/**
 * Determine if a user changed levels. Level change defined as a new, different membership level within 24 hours.
 *
 * @param {Object} $curr the current item
 * @param {Object} $next the next item
 * @return {Boolean}
 */
function pmpro_is_level_change($curr, $next) {
  if ($curr->user_id !== $next->user_id) {
    return false;
  }

  if ($curr->status !== 'cancelled' && $curr->status !== 'changed') {
    return false;
  }

  if ($curr->enddate == null || $curr->enddate == '0000-00-00 00:00:00') {
    return false;
  }

  if ($curr->membership_id == $next->membership_id) {
    return false;
  }

  if ((strtotime($next->startdate) - strtotime($curr->enddate)) < (DAY_IN_SECONDS)) {
    return true;
  }

  return false;
}

/**
 * Determine if a user is a returning customer.
 * A returning customer is defined as resubscribing to the same membership level within 30 days.
 *
 * @param {Object} $curr the current item
 * @param {Object} $next the next item
 * @return {Boolean}
 */
function pmpro_is_returning_customer($curr, $next) {
  if ($curr->user_id !== $next->user_id) {
    return false;
  }

  if ($curr->status !== 'cancelled') {
    return false;
  }

  if ($curr->enddate == null || $curr->enddate == '0000-00-00 00:00:00') {
    return false;
  }

  if (
    ($curr->membership_id == $next->membership_id) &&
    (strtotime($next->startdate) - strtotime($curr->enddate)) < (30 * DAY_IN_SECONDS)
  ) {
    return true;
  }

  return false;
}

function pmpro_array_sort_by_column(&$arr, $col, $dir = SORT_ASC) {
  $sort_col = array();
  foreach ($arr as $key => $row) {
    $sort_col[$key] = $row[$col];
  }
  array_multisort($sort_col, $dir, $arr);
}
