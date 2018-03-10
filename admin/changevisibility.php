<?php
	
	$fpIndex = fopen('../db/index.json', 'r+');
	$index = (array)json_decode(fread($fpIndex, filesize('../db/index.json')),true);
	$fileToChange = $_POST['key'];
	if ($index['files'][$fileToChange]['hidden'])
		$index['files'][$fileToChange]['hidden'] = 0;
	else
		$index['files'][$fileToChange]['hidden'] = 1;
	rewind($fpIndex);
	fwrite($fpIndex, json_encode($index));
	fclose($fpIndex);
	echo 'New status: '.$index['files'][$fileToChange]['hidden'];
?>