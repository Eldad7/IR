<?php
	$filesArr = scandir('../toparse');
	foreach ($filesArr as $key => $value) {
		if (!in_array($value, array('.','..'))){
			$filename = str_replace('.txt', '', $value);
			$fields = array('file' => $filename, 'author' => 'Anonymous Poet');
			$postString = http_build_query($fields, '', '&');
			$ch = curl_init('parser.php');
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $postString);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			$response = curl_exec($ch);
			curl_close($ch);
		}
	}

	echo 'Success';


?>