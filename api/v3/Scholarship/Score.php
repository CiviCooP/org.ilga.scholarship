<?php
use CRM_Scholarship_ExtensionUtil as E;

/**
 * Scholarship.Score API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_scholarship_score_spec(&$spec) {
}

/**
 * Scholarship.Score API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_scholarship_score($params) {

  $returnValues = [];

  if ($params['case_id']) {
    $dao = CRM_Core_DAO::executeQuery("
      select id from civicrm_case where case_type_id = %1 and id = %2", [
      1 => [variable_get(ILGA_SCHOLARSHIP_CASE_TYPE), 'Integer'],
      2 => [$params['case_id'], 'Integer'],
    ]);
    $returnValues['case_id'] = $params['case_id'];
  }
  else {
    $dao = CRM_Core_DAO::executeQuery("
        select id from civicrm_case where case_type_id = %1", [
      1 => [variable_get(ILGA_SCHOLARSHIP_CASE_TYPE), 'Integer'],
    ]);
  }
  while ($dao->fetch()) {
    $score = new CRM_Scholarship_Score($dao->id);
    $score->process();
  }
  return civicrm_api3_create_success($returnValues, $params, 'Scholarship', 'Score');
}
