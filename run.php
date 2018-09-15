<?php

   civicrm_initialize();

   $score = new CRM_Scholarship_Score(785);
   $score->process();
   print_r($score);


