<?php
	$fileName = 'db/'.$_POST['href'].'.txt';
	$fp = fopen($fileName, 'r+');
	$file = '';
	if (is_numeric($_POST['href'])){
		while (!feof($fp)){
			$file.=fgets($fp);
			foreach ($_POST['values'] as $key => $value) {
				$file = str_replace(' '.strtolower($value).' ',' <b>'.strtolower($value).'</b> ', $file);
				$file = str_replace(' '.strtoupper($value).' ',' <b>'.strtoupper($value).'</b> ', $file);
				$file = str_replace(ucfirst($value),' <b>'.ucfirst($value).'</b> ', $file);

			}
			$file.='</br>';
		}
	}
	else{
		$file = fread($fp, filesize($fileName));
	}
	fclose($fp);
	echo $file;
?>