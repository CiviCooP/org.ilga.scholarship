<?php
/**
 * Calculcates the total score of an evaluation.
 *
 * @author Klaas Eikelboom (CiviCooP)
 * @date 25 juni 2018
 * @license AGPL-3.0
 */

class CRM_Scholarship_Evaluation {


  private $activityId;
  private $caseId;
  private $sum;

  private static $singleton = null;

  /**
   * ilga_scholarship_evaluation constructor.
   *
   * @param $activityId
   */
  public function __construct($activityId) {
    $this->activityId = $activityId;
  }

  /**
   * find caseId - uses objects activity Id
   */
  private function findCaseId() {

    $this->caseId = CRM_Core_DAO::singleValueQuery("
       select case_id from civicrm_case_activity
       where activity_id = %1", [
      1 => [$this->activityId, 'Integer'],
    ]);
  }

  /**
   * calculates the sum of all the score on evaluation activities
   */
  private function calculateSum() {
    $config = self::config();
    // remark that the is_current_revision must be true
    // sometimes on an update of a case activity the old version is
    // archived. The sum must be calculated on current versions
    $sql = "select sum({$config['scoreField']}) from civicrm_case_activity ca
    join {$config['scoreTable']} cv on (ca.activity_id=cv.entity_id)
    join civicrm_activity act on (act.id = ca.activity_id and act.is_deleted=0 and act.is_current_revision=1)
    where ca.case_id=%1";
    $this->sum = CRM_Core_DAO::singleValueQuery($sql,[
      1 => [$this->caseId, 'Integer'],
    ]);
  }

  /**
   * Store the total result in the case
   * @throws \CiviCRM_API3_Exception
   */
  private function store(){

    civicrm_api3('Case','create',[
      'id' => $this->caseId,
      variable_get(ILGA_SCHOLARSHIP_EVALUATION_TOTAL_CUSTOM_FIELD) => $this->sum,
    ]);
  }

  /**
   * @throws \CiviCRM_API3_Exception
   */
  public function process() {
    $this->findCaseId();
    $this->calculateSum();
    $this->store();
  }

  /**
   * @return array the config singleton object, if it not exists it is created
   * @throws \CiviCRM_API3_Exception
   */
  private static function config(){
    if(!self::$singleton){
      $scoreFieldId = substr(variable_get(ILGA_SCHOLARSHIP_EVALUATION_CUSTOM_FIELD),7); // remove the 'custom_' part
      $result = civicrm_api3('CustomField', 'getsingle', array(
        'id' => $scoreFieldId,
      ));
      self::$singleton['scoreField'] = $result['column_name'];
      self::$singleton['scoreTable'] = civicrm_api3('CustomGroup', 'getvalue', array(
        'return' => "table_name",
        'id' => $result['custom_group_id'],
      ));
    }
    return self::$singleton;
  }

}