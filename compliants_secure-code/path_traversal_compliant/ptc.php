
<?php 

    $whitelist = array("ptc_1.html","ptc_2.html")  ; 
    if (isset ($_GET ['page']))
    {

      if(in_array($_GET['page'],$whitelist))      {    
          echo file_get_contents($_GET['page']);
      }
      else{
        echo "no way";
      }
    }
    
    else{

        echo file_get_contents("ptc_1.html");
    }

  ?>