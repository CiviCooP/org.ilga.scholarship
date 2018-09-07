<?php
/**
 * Created by PhpStorm.
 * User: klaas
 * Date: 17-8-18
 * Time: 15:53
 */

class CRM_Scholarship_Utils {

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
}