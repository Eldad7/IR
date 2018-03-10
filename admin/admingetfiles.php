<?php
	$fpIndex = fopen('../db/index.json', 'r+');
	$index = (array)json_decode(fread($fpIndex, filesize('../db/index.json')),true);
	fclose($fpIndex);
	echo json_encode($index['files']);
?>