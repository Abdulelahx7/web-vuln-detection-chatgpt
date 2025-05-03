<?php

if (isset($_GET["search_word"])){
	$searched = $_GET["search_word"];
	$searched = filter_var($searched,FILTER_SANITIZE_STRING,FILTER_FLAG_STRIP_HIGH);
}
else{
	$searched = "Make a search.";

}

 
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title>SEARCH BY NAME</title>
</head>
<body style="margin-top: 100px; text-align: center;">
<h1>This site use the user input in JavaScript.<h1> 
<p id="result"></p>


<script type="text/javascript">

	var x = '<?php echo htmlentities($searched)?>';

	document.getElementById("result").innerHTML = x;
	
</script>

<form id="search" action="" method="GET">
	<input id = "searched" name = "search_word" type="text" placeholder="Search Product">
	<input type="submit" name="Ara"> 
</form>
<br><br>


</body>
</html>
