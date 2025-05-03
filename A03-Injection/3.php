<?php 


    if(isset($_GET['submit'])){

        echo "Hello".htmlspecialchars($_GET['uname'],ENT_QUOTES,'UTF-8');
    }
    else{
        echo "Do smth";
    }

?>


<!DOCTYPE html>


<form method="GET" action="<?php echo $_SERVER['PHP_SELF'] ?> ">
    Input: <input name = "uname">
    <button name="submit" type="submit">Submit</button>
</form>


</html>