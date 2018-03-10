<?php
	$string = $_GET['search'];
	echo '<pre>'.$string.'</pre>';
	$fpIndex = fopen('db/index.json', 'r+');
	$index = (array)json_decode(fread($fpIndex, filesize('db/index.json')),true);
	$string = str_replace(array('.',';',',',':','"',"'",'!','?','/','\n','\r','\n\r','','`'), '', $string);
	$string = trim($string);
	$wordsArr = explode('(', $string);
	$andArray = array();
	$orArray = array();
	$wordArray = array();
	$notArray = array();
	$wordsArray = array();
	$toSearchArray = array();
	$parenthesisFlag = false;
	$wordsArray = explode(' ', $string);
	$tempArray = array();
	$tmpLocator = 0;
	foreach ($wordsArray as $key => $value) {
		$tmpLocator += strpos(substr($string, $tmpLocator),$value);
		if (strcmp('(',substr($value, 0,1))==0){
			//echo $value.PHP_EOL;
			parenthesis(explode(')', substr($string, $tmpLocator)));
		}
	}

	function parenthesis($arr){
		$array = explode(' ', $arr[0]);
		echo '<pre>';
		print_r($array);
		echo '</pre>';
		$tempArray = array();
		$orFlag = false;
		$andFlag = false;
		foreach ($array as $key => $value) {
			$value = str_replace('(', '', $value);

			if (strcmp('+',$value)==0){
				$andFlag = true;
			}
			else if (strcmp('|',$value)==0){
				$orFlag = true;
			}
			else{
				foreach ($GLOBALS['index']['index'] as $word => $positions) {
					if (strcmp($value,$word)==0){
						array_push($tempArray,$positions['locations']);
						break;
					}
				}
			}
			//unset($GLOBALS['wordsArray'][$key]);
		}
		$firstWord = array();
		$secondWord = array();
		$flag = true;
		foreach ($tempArray as $tmpArray) {
			foreach ($tmpArray as $key => $value){
				$locations = explode(',',$value);
				if ($flag){
					$firstWord[$locations[0]] = array();
					array_push($firstWord[$locations[0]],$locations[1].','.$locations[2]);
				}
				else{
					$secondWord[$locations[0]] = array();
					array_push($secondWord[$locations[0]],$locations[1].','.$locations[2]);
				}
			}
			$flag = false;
		}

		if ($andFlag){	
			foreach ($firstWord as $key => $value) {
				if (!array_key_exists($key, $secondWord)){
					unset($firstWord[$key]);
				}
			}
			echo '<pre>';
			print_r($firstWord);
			echo '</pre>';	
		}
		if ($orFlag){
			foreach ($secondWord as $key => $file) {
				foreach ($file as $fkey => $value) {
					if (!isset($firstWord[$key]))
						$firstWord[$key] = array();
					array_push($firstWord[$key],$value);
				}
			}
			echo '<pre>';
			print_r($firstWord);
			echo '</pre>';
		}
		return($firstWord);
	}
	function key_compare_func($key1, $key2)
	{
	    if ($key1 == $key2)
	        return 0;
	    else if ($key1 > $key2)
	        return 1;
	    else
	        return -1;
	}
	/*
		$wordArr = explode('(',$string);
		$andArray = array();
		$orArray = array();
		foreach ($wordArr as $key => $value) {
			$txtArr = explode(' ',$value);
			foreach ($txtArr as $key => $value) {
				if (strpos($value, '|')>0){
					array_push($orArray,$value[$key-1]);
					array_push($andArray,$value[$key]);
				}
				else{
					array_push($andArray,$value[$key-1])
				}
			}

			/oreach ($txtArr as $key => $value) {
				foreach ($index['index'] as $word => $positions) {
					if (strcmp($value,$word)==0){
						array_push($tempArray,$positions);
						break;
					}
				}
				
		}
	*/
	/*$txtArr = explode(' ', $string);
	$tempArray = array();
	foreach ($txtArr as $key => $value) {
		foreach ($index['index'] as $word => $positions) {
			if (strcmp($value,$word)==0){
				array_push($tempArray,$positions);
				break;
			}
		}
		
	}
	$stringArray = array();
	foreach ($tempArray as $tmp) {
		foreach ($tmp as $key => $value) {
			array_push($stringArray,$value);
		}
	}
	sort($stringArray);
	$filesArray = array();
	foreach ($stringArray as $key => $value) {
		$locations = explode(',',$value);
		if (isset($filesArray[$locations[0]][$locations[1]]))
			array_push($filesArray[$locations[0]][$locations[1]],$locations[2]);
		else{
			$filesArray[$locations[0]][$locations[1]] = array();
			array_push($filesArray[$locations[0]][$locations[1]],$locations[2]);
		}
	}
	$sequenceArray = array();
	foreach ($filesArray as $key => $line) {
		foreach ($line as $position => $value) {
			if (count($value)>0){
				$seq = 1;
				for ($i=0; $i < count($value)-1; $i++) { 
					if ($value[$i]+1 == $value[$i+1]){
						$seq++;
					}
				}
				
				if (isset($sequenceArray[$seq][$key])){
					if ($sequenceArray[$seq][$key] < $seq){
						$sequenceArray[$seq][$key] = $position;
					}
				}
				else{
					$sequenceArray[$seq][$key] = $position;
				}
				
			}
		}
	}
	krsort($sequenceArray);
	$resultArray = array();
	foreach ($sequenceArray as $sequence => $files) {
		foreach ($files as $file => $line) {
			$fp = fopen('db/'.$file.'.txt', 'r');
			$preview = '';
			if ($line<2){
				for ($i=0; $i < 4; $i++) { 
					if ($i == $line)
						$preview.='<i>';
					$preview.=trim(fgets($fp));
					if ($i == $line)
						$preview.='</i>';
					$preview.='<br/>';
				}
			}
			else{
				for ($i=0; $i<$line-1; $i++)
					fgets($fp);
				for ($i=0; $i < 4; $i++) {
					if ($i == $line)
						$preview.='<i>';
					$preview.=trim(fgets($fp));
					if ($i == $line)
						$preview.='</i>';
					$preview.='<br/>';
				}
			}
			if (!isset($resultArray[$file]))
				$resultArray[$file] = array($index['files'][$file],$preview);
		}
		$counter = 0;
    	foreach ($resultArray as $key => $value) {
    		echo "<div><h1><a href ='db/".$key.".txt' target=_blank>".substr($value[0]['name'], 0,strpos($value[0]['name'], '.txt'))."</a></h1>";
    		echo "<h6>By ".$value[0]['author']."</h6>";
    		echo "<h4>".$value[1].'</h4></div>';
    		if (++$counter==0){
    			$stopper=$key;
    			break;
    		}
    	}
	}*/
	fclose($fpIndex);
?>