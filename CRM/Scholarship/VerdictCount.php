<?php
/**
 * Created by PhpStorm.
 * User: klaas
 * Date: 29-8-18
 * Time: 12:46
 */

class CRM_Scholarship_VerdictCount {

  private $contactId;
  private $verdictSummary;

  /**
   * CRM_Scholarship_VerdictCount constructor.
   *
   * @param $contactId
   */
  public function __construct($contactId) {
    $this->contactId = $contactId;
  }

  private function calculateSummary() {
    $dao = CRM_Core_DAO::executeQuery("
    select cv.evaluation_110 as eval,
       ov1.label as label,
       count(cv.id) as count
       from   civicrm_activity ac
       join   civicrm_value_evaluation_8 cv on (cv.entity_id = ac.id)
       join   civicrm_option_value ov1 on (ov1.value = cv.evaluation_110)
       join   civicrm_option_group og1 on (ov1.option_group_id = og1.id and og1.name='evaluation_20180817133425')
       join   civicrm_activity_contact aco on (aco.activity_id = ac.id)
       join   civicrm_option_value ov2 on (ov2.value = aco.record_type_id and ov2.name='Activity Assignees')
       join   civicrm_option_group og2 on (ov2.option_group_id = og2.id and og2.name='activity_contacts')
       where  aco.contact_id = %1
       group  by  cv.evaluation_110,ov1.label",[ 1=> [$this->contactId,'Integer']]);

    $this->verdictSummary='';
    while($dao->fetch()){
      $this->verdictSummary .= "{$dao->label} {$dao->count} \n";
    }
  }

  private function store() {
    $result = civicrm_api3('Contact', 'create', [
      'id' => $this->contactId,
      'custom_'.variable_get(ILGA_SCHOLARSHIP_VERDICT_COUNT_CUSTOM_FIELD) => $this->verdictSummary,
    ]);
  }

  public function process() {
    $this->calculateSummary();
    $this->store();
  }

}