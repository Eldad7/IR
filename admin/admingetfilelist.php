<?php
	$filesArr = scandir('../toparse');
	$results = array();
	foreach ($filesArr as $key => $value) {
		if (!in_array($value, array('.','..')))
			array_push($results, $value);
	}
	echo json_encode($results);
?>