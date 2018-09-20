<?php

   civicrm_initialize();

   $score = new CRM_Scholarship_Score(873);
   $score->process();
   print_r($score);


