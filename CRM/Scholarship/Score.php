<?php
/**
 * Contains all the logic to calculate all the scholarship score's
 *
 * @author Klaas Eikelboom (CiviCooP)
 * @date 25 juni 2018
 * @license AGPL-3.0
 */

class CRM_Scholarship_Score {

  private static $countryList = [
    // Africa
    'DZ', // Algeria
    'AO', // Angola
    'BJ', // Benin
    'BF', // Burkino Faso
    'CV', // Cap-Vert
    'CI', // Cote d'Ivoire
    'DJ', // Djiboutie
    'GM', // Gambia
    'GN', // Guinnee
    'GW', // Guinnee Bisau
    'LS', // Lesotho
    'LR', // Liberia
    'LY', // Libia
    'MG', // Madagascar
    'ML', // Mali
    'MA', // Morocco
    'MU', // Mauritius
    'MZ', // Mozambique
    'NE', // Niger
    'RW', // Rwanda
    'EH', // Western Sahara
    'ST', // Saint Tome
    'SC', // Sechelles
    'SL', // Sierra Leona
    'SD', // Sudan
    'TG', // Togo
    'TN', // Tunesia
    // West Asia
    'BA', //Bahrain
    'IR', // Iran
    'IQ', // Iraq
    'JO', // Jordan
    'KW', // Kuwait
    'LB', // Lebanon
    'OM', // Oman
    'PS', // Palestine
    'QA', // Qatar
    'SA', // Saudi Arabia
    'SY', // Syria
    'AE', // United Arab Emirates
    'YE', // Yemen
    'AF', // Afghanistan
    'BT', // Bhutan
    'MV', // Maldives
    'LK', // Sri Lanka
    'JP', // Japan
    'MO', // Macou
    'MN', // Mongolia
    'KP', // North Korea
    'BN', // Brunei
    'KH', // Cambodia
    'TL', // East Timor
    'LA', // Laos
    'MM', // Myanmar
    // Europe
    'AL', // Albania
    'AM', // Armenia
    'AZ', // Azerbaijan
    'BY', // Belarus
    'BA', // Bosnia
    'MK', // FYRON or Macedonia
    'GE', // Georgia
    'KZ', // Kazakhstan
    'KG', // Kyrgystan
    'MD', // Moldova
    'ME', // Montenegro
    'RO', // Romania
    'RU', // Russia
    'TJ', // Tajikistan
    'TR', // Turkey
    'TM', // Turkmenistan
    'UA', // Ukraine
    'UZ', // Uzbekistan
    // Carribean Islands and Countries

    'CU', // Cuba
    'HT', // Haiti
    'DO', // Domican Republic
    'PR', // Puerto Rico
    'JM', // Jamaica
    'TT', // Trinidad and Tobago
    'GP', // Guadeloupe
    'MQ', // Martinique
    'BS', // Bahamas
    'BB', // Barbados
    'LC', // Saint Lucia
    'CW', // Curucao
    'AW', // Aruba
    'VC', // Saint Vincent and the Grenadines
    'VI', // United States Virgin Islands
    'GD', // Grenada
    'AG', // Antigua and Barbuda
    'DM', // Dominica
    'KY', // Caymand Islands
    'KN', // Saint Kitts and Nevis
    'SX', // Sint Maarten
    'TC', // Turks and Caicos Islands
    'MF', // Saint Martin
    'VG', // British Virgin Islands
    'BQ', // Caribbean Netherlands
    'AI', // Anguilla
    'BL', // Saint Barthelemy
    'MS', // Montserrat

    // Lac
    'BZ', // Belize
    'CL', // Chile
    'CR', // Costa Rica
    'SV', // El Salvador
    'GT', // Guatemala
    'GY', // Gyana
    'HN', // Honduras
    'PY', // Paraguay

    // Oceania
    'AS', // American Samoa
    'CK', // Cook Islands
    'FJ', // Fiji
    'GU', // Guam
    'KI', // Kirabatie
    'NC', // New Caledonia
    'NU', // Niue
    'PW', // Palau
    'PG', // Papua New Guinea
    'WS', // Samoa
    'SB', // Soloman Islands
    'PF', // Tahiti
    'TK', // Tokelau
    'TO', // Tonga
    'TV', // Tuvaly
    'VU', // Vanuatu

  ];

  private static $identifyList = [
    'Asexual' => 1,
    'Bisexual'=> 1,
    'Gay' => 0,
    'Heterosexual' => 0,
    'Lesbian' => 1,
    'Pansexual'=> 1,
    'Queer' => 1,
    'Do not know' => 0,
    'Other'=>1,
  ];

  private static $genderList = [
    'Agender' => 1 ,
    'Female' => 1 ,
    'Gender_Fluid' => 1 ,
    'Genderqueer' => 1 ,
    'Male' =>  0,
    'Trans_Female_to_Male' => 1 ,
    'Trans_Male_to_Female' => 1,
    'Two_spirit' => 1,
    'Do_not_know' => 0,
    'Other' => 1,
    'Cisgender_Male' => 0,
    'Cisgender_Female' => 1,
    'Non_binary' => 1,
    'Bakla' => 1,
    'Fa_afafine' => 1,
    'Fakaleitis' => 1 ,
    'Hijra' => 1,
    'Kotis' => 1,
    'Toms' => 1,
    'Travestis' => 1,
  ];

  private static $hivSexList = [
    'you are a person living with HIV' => 1,
    'you are a sex worker' =>1,
  ];

  private $caseId;
  private $disability;
  private $identity;
  private $genderIdentity;
  private $intersex;
  private $indigenous;
  private $marginalised;
  private $hiv;
  private $sexworker;


  private $receivedScholarship;
  private $workShop;
  private $birthDate;
  private $nationality;
  private $country;
  private $country_code;
  private $stateProvince;
  private $stateProvinceName;
  private $referenceDateYounger30;
  private $referenceDateOlder60;

  /**
   * @var int Result of the score calculation
   */
  private $score= 0;

  /**
   * @var array Explanation how the result is calculated
   */
  private $explanation = [];

  /**
   * _Score constructor.
   *
   * @param $caseId for which the score must be calculated
   */
  public function __construct($caseId) {
    $this->caseId = $caseId;
  }

  /**
   * Reads score information form the database
   * @throws \CiviCRM_API3_Exception
   */
  public function getInfo(){
    $values = civicrm_api3('Case','getsingle',['id'=>$this->caseId]);
    $this->disability = $values[variable_get(ILGA_SCHOLARSHIP_DISABILITY_CUSTOM_FIELD)];
    $this->identity = $values[variable_get(ILGA_SCHOLARSHIP_SEXUAL_IDENTITY_CUSTOM_FIELD)];
    $this->receivedScholarship = $values[variable_get(ILGA_SCHOLARSHIP_RECEIVED_CUSTOM_FIELD)];
    $this->workShop = $values[variable_get(ILGA_SCHOLARSHIP_WORKSHOP_CUSTOM_FIELD)];


    $this->genderIdentity = $values[variable_get(ILGA_SCHOLARSHIP_GENDER_IDENTITY_CUSTOM_FIELD)];
    $this->intersex = $values[variable_get(ILGA_SCHOLARSHIP_INTERSEX_CUSTOM_FIELD)];
    $this->indigenous = $values[variable_get(ILGA_SCHOLARSHIP_INDIGENOUS_CUSTOM_FIELD)];
    $this->marginalised = $values[variable_get(ILGA_SCHOLARSHIP_MARGINALISED_CUSTOM_FIELD)];
    $this->hiv = $values[variable_get(ILGA_SCHOLARSHIP_HIV_CUSTOM_FIELD)];
    $this->sexworker = $values[variable_get(ILGA_SCHOLARSHIP_SEXWORKER_CUSTOM_FIELD)];
    $this->nationality = $values[variable_get(ILGA_SCHOLARSHIP_NATIONALITY_CUSTOM_FIELD)];

    $client_id = $values['client_id'];
    $contactId = $client_id[1];

    $contact = civicrm_api3('Contact','getsingle',['id' => $contactId]);
    $this->birthDate = $contact['birth_date'];

    $this->stateProvince   = $contact['state_province'];
    $this->stateProvinceName   = $contact['state_province_name'];



    if($this->nationality) {
      $result = civicrm_api3('Country', 'getsingle', [
        'id' =>$this->nationality,
      ]);
      $this->country = $result['name'];
      $this->country_code = $result['iso_code'];
    }

    $date = variable_get(ILGA_SCHOLARSHIP_REFERENCE_DATE);
    $this->referenceDateYounger30 = ($date['year'] - 30) .'-' . str_pad($date['month'],2,'0',STR_PAD_LEFT) . '-' .str_pad($date['day'],2,'0',STR_PAD_LEFT);
    $this->referenceDateOlder60 = ($date['year'] - 60) .'-' . str_pad($date['month'],2,'0',STR_PAD_LEFT) . '-' .str_pad($date['day'],2,'0',STR_PAD_LEFT);

  }

  /**
   * Workhorse of the procedure. Does the calculation
   *
   * @throws \CiviCRM_API3_Exception
   */
  public function calculate() {
    /*
     * Each step has the same structure. Points are added to (or extracted from
     * the score). In the explanation the reason is described
     */


    $this->score = 0;

    if(CRM_Scholarship_Utils::applicationFromFullMember($this->caseId)){
      $this->score += 3;
      $this->explanation[] = '3 because the represented organisation is a full member';
    }

    if ($this->birthDate > $this->referenceDateYounger30) {
      $this->score += 3;
      $this->explanation[] = '3 for being younger than 30 on conference date';
    }
    if ($this->birthDate < $this->referenceDateOlder60) {
      $this->score += 3;
      $this->explanation[] = '3 for being older than 60 on conference date';
    }
    if (in_array($this->country_code, $this::$countryList)) {
      $this->score += 1;
      $this->explanation[] = "1 points for having the nationality of {$this->country}";
    }

    if(is_array($this->identity)){
      $points = 0;
      foreach($this->identity as $identityId){
        $label = $result = civicrm_api3('OptionValue', 'getvalue', array(
          'return' => "label",
          'value' => $identityId,
          'option_group_id' => "how_do_you_identify__20180715101511",
        ));
        if(self::$identifyList[$label]){
          $points += 1;
          $this->explanation[] = "1 points for identity {$label}";
        }
      }
      if($points>2){
        $points = 2;
        $this->explanation[]= "max 2 points for identity";
      }
      $this->score +=$points;
    }

    if(is_array($this->genderIdentity)){
      $points = 0;
      foreach($this->genderIdentity as $genderIdentityId){
        $label = $result = civicrm_api3('OptionValue', 'getvalue', array(
          'return' => "name",
          'value' => $genderIdentityId,
          'option_group_id' => "your_gender_20180715102053",
        ));
        if(self::$genderList[$label]){
          $points += 1;
          $this->explanation[] = "1 points for gender identity {$label}";
        }
      }
      if($points>3){
        $points = 3;
        $this->explanation[]= "max 3 points for gender identity";
      }
      $this->score +=$points;
    }

    if($this->intersex){
      $this->score+=3;
      $this->explanation[]= "3 point because of intersex";
    }

    if($this->indigenous){
      $this->score+=3;
      $this->explanation[]= "3 point because of member of indeginous or traditional group";
    }

    if($this->disability){
      $this->score+=3;
      $this->explanation[]= "3 point because of disability";
    }

    if($this->marginalised){
      $this->score+=1;
      $this->explanation[]= "1 point because of member of a marginalised community";
    }

    if(is_array($this->hiv)){
      $points = 0;
      foreach($this->hiv as $hivId){
        if($hivId) {
          $label = $result = civicrm_api3('OptionValue', 'getvalue', [
            'return' => "name",
            'value' => $hivId,
            'option_group_id' => "are_you_hiv_positive_sex_worker__20180715104805",
          ]);
          if (self::$hivSexList[$label]) {
            $points += 1;
            $this->explanation[] = "2 points for {$label}";
          }
        }
      }
      $this->score +=$points;
    }


    if(!($this->receivedScholarship)){
      $this->score+=1;
      $this->explanation[]= "1 point because first scholarship before";
    }



  }

  /**
   * Write the result back to the case custom fields
   * @throws \CiviCRM_API3_Exception
   */
  public function set(){

    civicrm_api3('Case','create',array(
        'id' =>$this->caseId,
        variable_get(ILGA_SCHOLARSHIP_SCORE_CUSTOM_FIELD) => $this->score,
        variable_get(ILGA_SCHOLARSHIP_SCORE_EXPLANATION_CUSTOM_FIELD) => implode("\n",$this->explanation)
      )
    );
  }

  /**
   * Umbrella procedure that executes all the code.
   * @throws \CiviCRM_API3_Exception
   */
  public function process(){
    $this->getInfo();
    $this->calculate();
    //echo $this->score;
    //print_r($this->explanation);
    $this->set();
  }

  public static function printCountries(){

    foreach(CRM_Scholarship_Score ::$countryList as $countryCode){
      $countryName = civicrm_api3('Country','getvalue',array(
        'iso_code' => $countryCode,
        'return'  => 'name'
      ));
      echo "$countryCode => $countryName \n";
    }

  }

}