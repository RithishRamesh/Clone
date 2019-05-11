<?php
/*
 * IMathAS: Assessment launch endpoint
 * (c) 2019 David Lippman
 *
 * Method: GET
 * Query string parameters:
 *  aid   Assessment ID
 *  cid   Course ID
 *  uid   Optional. Only allowed for teachers, to load student's assessment
 *
 * Returns: assessInfo object
 */

$init_skip_csrfp = true; // TODO: get CSRFP to work
$no_session_handler = 'onNoSession';
require_once("../init.php");
require_once("./common_start.php");
require_once("./AssessInfo.php");
require_once("./AssessRecord.php");
require_once('./AssessUtils.php');

header('Content-Type: application/json; charset=utf-8');

//validate inputs
check_for_required('GET', array('aid', 'cid'));
$cid = Sanitize::onlyInt($_GET['cid']);
$aid = Sanitize::onlyInt($_GET['aid']);
if ($isActualTeacher && isset($_GET['uid'])) {
  $uid = Sanitize::onlyInt($_GET['uid']);
} else {
  $uid = $userid;
}

//load settings without questions
$assess_info = new AssessInfo($DBH, $aid, $cid, false);
$assess_info->loadException($uid, $isstudent, $studentinfo['latepasses'] , $latepasshrs, $courseenddate);
if ($isstudent) {
  $assess_info->applyTimelimitMultiplier($studentinfo['timelimitmult']);
}

//check to see if prereq has been met
$assess_info->checkPrereq($uid);

//load user's assessment record - start with scored data
$assess_record = new AssessRecord($DBH, $assess_info, false);
$assess_record->loadRecord($uid);

//fields to extract from assess info for inclusion in output
$include_from_assess_info = array(
  'name', 'summary', 'available', 'startdate', 'enddate', 'original_enddate',
  'extended_with', 'timelimit', 'timelimit_type', 'points_possible',
  'submitby', 'displaymethod', 'groupmax', 'isgroup', 'showscores', 'viewingb',
  'can_use_latepass', 'allowed_attempts', 'retake_penalty', 'exceptionpenalty',
  'timelimit_multiplier', 'latepasses_avail', 'latepass_extendto', 'keepscore',
  'noprint'
);
$assessInfoOut = $assess_info->extractSettings($include_from_assess_info);

// livepoll server location, if needed
if ($assessInfoOut['displaymethod'] === 'livepoll') {
  $assessInfoOut['livepoll_server'] = $CFG['GEN']['livepollserver'];
}

// indicate if teacher or tutor user
$assessInfoOut['can_view_all'] = $canViewAll;
$assessInfoOut['is_teacher'] = $isteacher;

//set is_lti and is_diag
$assessInfoOut['is_lti'] = isset($sessiondata['ltiitemtype']);
$assessInfoOut['is_diag'] = isset($sessiondata['isdiag']);

//set has password
$assessInfoOut['has_password'] = $assess_info->hasPassword();

//get attempt info
$assessInfoOut['has_active_attempt'] = $assess_record->hasActiveAttempt();
//get time limit expiration of current attempt, if appropriate
if ($assessInfoOut['has_active_attempt'] && $assessInfoOut['timelimit'] > 0) {
  $assessInfoOut['timelimit_expires'] = $assess_record->getTimeLimitExpires();
}

// if not available, see if there is an unsubmitted scored attempt
if ($assessInfoOut['available'] !== 'yes') {
  $assessInfoOut['has_unsubmitted_scored'] = $assess_record->hasUnsubmittedScored();
}

//get prev attempt info
$assessInfoOut['prev_attempts'] = $assess_record->getSubmittedAttempts();
$assessInfoOut['scored_attempt'] = $assess_record->getScoredAttempt();

if (!$assessInfoOut['has_active_attempt']) {
  if ($assessInfoOut['submitby'] == 'by_question') {
    $assessInfoOut['can_retake'] = (count($assessInfoOut['prev_attempts']) == 0);
  } else {
    $assessInfoOut['can_retake'] = (count($assessInfoOut['prev_attempts']) < $assessInfoOut['allowed_attempts']);
  }
}

//load group members, if applicable
if ($assessInfoOut['isgroup'] > 0 && !$canViewAll) {
  list ($stugroupid, $groupmembers) = AssessUtils::getGroupMembers($uid, $assess_info->getSetting('groupsetid'));
  $assessInfoOut['group_members'] = array_values($groupmembers);
  $assessInfoOut['stugroupid'] = $stugroupid;
  if ($assessInfoOut['isgroup'] == 2) {
    if (count($assessInfoOut['group_members']) === 0) {
      // no group members yet - add self
      $assessInfoOut['group_members'][] = $userfullname;
    }
    //if can add group members, get available people
    $query = 'SELECT iu.id,iu.FirstName,iu.LastName FROM imas_users AS iu ';
    $query .= 'JOIN imas_students AS istu ON istu.userid=iu.id AND istu.courseid=? ';
    $query .= 'WHERE iu.id NOT IN (SELECT isgm.id FROM imas_stugroupmembers AS isgm ';
    $query .= 'JOIN imas_stugroups as isg ON isg.id=isgm.stugroupid AND ';
    $query .= 'isg.groupsetid=?) AND iu.id<>? ORDER BY iu.FirstName, iu.LastName';
    $stm = $DBH->prepare($query);
    $stm->execute(array($cid, $assess_info->getSetting('groupsetid'), $uid));
    $assessInfoOut['group_avail'] = array();
    while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
      $assessInfoOut['group_avail'][$row['id']] = $row['FirstName'] . ' ' . $row['LastName'];
    }
  }
} else {
  if ($assessInfoOut['isgroup'] > 0) { // include for teachers to prevent errors
    $assessInfoOut['group_members'] = array();
  }
  $assessInfoOut['stugroupid'] = 0;
}

$assessInfoOut['userfullname'] = $userfullname;
if ($assessInfoOut['is_diag']) {
  $assessInfoOut['diag_userid'] = substr($username,0,strpos($username,'~'));
}

//output JSON object
echo json_encode($assessInfoOut);
