<?php
/**
 * Created by PhpStorm.
 * User: klaas
 * Date: 7-9-18
 * Time: 13:53
 */

function resolve($caseId) {

  civicrm_api3('Case', 'create', [
    'id' => $caseId,
    'status_id' => "Closed",
  ]);

  $result = civicrm_api3('Case', 'getsingle', [
    'return' => ["contact_id"],
    'id' => $caseId,
  ]);

  $clientId = array_shift($result['client_id']);

  $relationShipId = civicrm_api3('Relationship', 'getvalue', array(
    'return' => 'id',
    'contact_id_a' => $clientId,
    'relationship_type_id' => 18,
  ));

  if($relationShipId){
    civicrm_api3('Relationship','delete',['id'=>$relationShipId]);
  }
}

function cleanup(){
  echo "Cleanup \n";

  $sql="select c.id, r.contact_id_b from civicrm_case c
join civicrm_value_scholarship_application_7 a on c.id = a.entity_id
join civicrm_case_contact cc on (cc.case_id = c.id)
join civicrm_relationship r on (r.relationship_type_id=18 and r.contact_id_a=cc.contact_id)
where c.case_type_id = 4 
and (r.contact_id_b not in (5117,2252)) 
and score_107=9
and c.status_id=1";



  $dao = CRM_Core_DAO::executeQuery($sql);

  while($dao->fetch()){
    echo "Resolving $dao->id \n";
    resolve($dao->id);

  }
}
civicrm_initialize();
cleanup();


