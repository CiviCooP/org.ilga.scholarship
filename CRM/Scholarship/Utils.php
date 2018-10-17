<?php
/**
 * Created by PhpStorm.
 * User: klaas
 * Date: 17-8-18
 * Time: 15:53
 */

class CRM_Scholarship_Utils {

  /**
   * @param $caseId
   *
   * @return null|string
   */
  public static function applicationFromFullMember($caseId){
     $inClause = implode(',',variable_get(ILGA_SCHOLARSHIP_VALID_MEMBERSHIP_TYPES));
     $sql = 'select cr.contact_id_a from civicrm_case cs
             join   civicrm_case_contact cc on cc.case_id = cs.id
             join   civicrm_relationship cr on cc.contact_id = cr.contact_id_b and cr.relationship_type_id=%1
             join   civicrm_membership   mr on cr.contact_id_a = mr.contact_id
             where  cs.id= %2
             and    mr.membership_type_id in ('.$inClause.')';
     return  CRM_Core_DAO::singleValueQuery($sql,[
          1 => [variable_get(ILGA_SCHOLARSHIP_REPRESENTING_RELATION_TYPE),'Integer'],
          2 => [$caseId,'Integer'],
       ]
       );
  }


  /**
   * @param $sid
   * @param $evaluatorId
   *
   * @return null|string
   */
  public static function findActivityBySid($sid,$evaluatorId){
    $sessionEvaluationTable = ilga_scholarship_helper::custom_table_name('Sessions_proposal_evaluation');
    $submissionField = ilga_scholarship_helper::custom_column_name(variable_get(ILGA_SCHOLARSHIP_SUBMISSION_CUSTOM_FIELD));
    $sql = "select entity_id
            from   $sessionEvaluationTable cv
            join   civicrm_activity_contact ac on (ac.activity_id = cv.entity_id and ac.record_type_id=3)
            join   civicrm_activity a on ( a.id = ac.activity_id and a.is_current_revision=1 and a.is_deleted=0)
            where  $submissionField = %1 and ac.contact_id =%2";
    return CRM_Core_DAO::singleValueQuery($sql,[
      1 => [$sid,'Integer'],
      2 => [$evaluatorId,'Integer']
    ]);
  }

  /**
   * @param $proposerId
   * @param $evaluatorId
   * @param $sid
   * @param $verdict
   * @param $motivation
   *
   * @throws \CiviCRM_API3_Exception
   */
  public static function updateActivitySid($proposerId, $evaluatorId, $sid, $verdict, $motivation) {
    $apiParams = [
      variable_get(ILGA_SCHOLARSHIP_SUBMISSION_CUSTOM_FIELD) => $sid,
      variable_get(ILGA_SCHOLARSHIP_SESSION_VERDICT_CUSTOM_FIELD) => $verdict,
      'details' => $motivation,
      'activity_type_id' => "Session evaluation",
    ];

    $activityId = self::findActivityBySid($sid, $evaluatorId);
    if ($activityId) {
      $apiParams['id'] = $activityId;
      $result = civicrm_api3('Activity', 'create', $apiParams);
    }
    else {
      $apiParams['source_contact_id'] = $proposerId;
      $result = civicrm_api3('Activity', 'create', $apiParams);
      civicrm_api3('ActivityContact', 'create', [
        'activity_id' => $result['id'],
        'contact_id' => $evaluatorId,
        'record_type_id' => "Activity Assignees",
      ]);
      civicrm_api3('ActivityContact', 'create', [
        'activity_id' => $result['id'],
        'contact_id' => $evaluatorId,
        'record_type_id' => "Activity Targets",
      ]);
    }
  }

  static private function componentOptions($cid){
    module_load_include('inc', 'webform', 'includes/webform.submissions');
    $node = node_load(12);
    $options =[];
    $items = explode("\n",$node->webform['components'][$cid]['extra']['items']);
    foreach($items as $item){
      $eItem = explode('|',$item);
      if(isset($eItem[1])){
        $options[$eItem[0]]= $eItem[1];
      }
    }
    return $options;
  }

  static public function sessionVerdictResults(){
    $header = explode(',','sid,sessionProposer,sessionEvaluator,title,format,streams,objectives,outcomes,applied,verdictvalue,verdict');
    module_load_include('inc', 'webform', 'includes/webform.submissions');
    $sessionEvaluationTable = ilga_scholarship_helper::custom_table_name('Sessions_proposal_evaluation');
    $submissionField = ilga_scholarship_helper::custom_column_name(variable_get(ILGA_SCHOLARSHIP_SUBMISSION_CUSTOM_FIELD));
    $verdictField = ilga_scholarship_helper::custom_column_name(variable_get(ILGA_SCHOLARSHIP_SESSION_VERDICT_CUSTOM_FIELD));
    $verdictOptionGroupId = ilga_scholarship_helper::custom_column_optionGroupId(variable_get(ILGA_SCHOLARSHIP_SESSION_VERDICT_CUSTOM_FIELD));
    $rows[] = $header;
    $streamOptions = self::componentOptions(150);
    $formatOptions = self::componentOptions(101);
    $appliedOptions = self::componentOptions(88);
    $sql = "select act.id,
       proposer.display_name sessionProposer,
       evaluator.display_name sessionEvaluator,
       ac2.contact_id voter,
       cv.{$verdictField} verdictvalue,
       cv.{$submissionField} sid,
       ov3.label                  verdict
       from     civicrm_activity act
         join   civicrm_activity_contact ac1 on (ac1.activity_id = act.id)
         join   civicrm_option_value     ov1 on (ov1.value = ac1.record_type_id and ov1.name='Activity Source')
         join   civicrm_option_group     og1 on (ov1.option_group_id = og1.id and og1.name ='activity_contacts')
         join   civicrm_activity_contact ac2 on (ac2.activity_id = act.id)
         join   civicrm_option_value     ov2 on (ov2.value = ac2.record_type_id and ov2.name='Activity Assignees')
         join   civicrm_option_group     og2 on (ov2.option_group_id = og2.id and og2.name ='activity_contacts')
         join   {$sessionEvaluationTable} cv on act.id = cv.entity_id
         join   civicrm_option_value     ov3 on (ov3.value = cv.{$verdictField} and ov3.option_group_id= {$verdictOptionGroupId})
         join   civicrm_contact proposer     on (proposer.id=ac1.contact_id)
         join   civicrm_contact evaluator    on (evaluator.id=ac2.contact_id)
         where  act.activity_type_id = %1    and act.is_current_revision =1";
    $dao = CRM_Core_DAO::executeQuery($sql,
      [ 1 => [variable_get(ILGA_SCHOLARSHIP_SESSION_ACTIVITY_TYPE), 'Integer']]);
    while($dao->fetch()){
      $row['sid'] = $dao->sid;
      $row['sessionProposer'] = $dao->sessionProposer;
      $row['sessionEvaluator'] = $dao->sessionEvaluator;
      $submission = webform_get_submission(12,$dao->sid);
      $data = $submission->data;
      $row['title']=$data[100][0];
      $row['format']=$formatOptions[$data[101][0]];
      $streams = [];
      foreach($data[150] as $item){
        $streams[]=$streamOptions[$item];
      }
      $row['streams']=implode(',',$streams);
      $row['objectives']=$data[104][0];
      $row['outcomes']=$data[144][0];
      $row['applied']=$appliedOptions[$data[88][0]];
      $row['verdictvalue'] = $dao->verdictvalue;
      $row['verdict'] = $dao->verdict;
      $rows[] = $row;
    }
    return $rows;
  }
}