<?php

   civicrm_initialize();
   echo 'Hello';
   $score = new CRM_Scholarship_Score(769);
   $score->process();