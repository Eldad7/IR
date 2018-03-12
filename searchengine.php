<?php
	$string = $_GET['search'];
	echo '<pre>'.$string.'</pre>';
	$fpIndex = fopen('db/index.json', 'r+');
	$index = (array)json_decode(fread($fpIndex, filesize('db/index.json')),true);
	fclose($fpIndex);
	//Sort index by Hits
	arsort($index['index']);
	$fpStopWords = fopen('db/stopwords.json', 'r+');
	$stopWords = (array)json_decode(fread($fpStopWords, filesize('db/stopwords.json')),true);
	fclose($fpStopWords);
	$string = str_replace(array('.',';',',',':','"',"'",'!','?','/','\n','\r','\n\r','','`'), '', $string);
	$string = trim($string);
	$wordsArr = explode('(', $string);
	$resultsArray = array();
	$parenthesisFlag = false;
	$wordsArray = explode(' ', $string);
	$tempArray = array();
	$tmpLocator = 0;
	foreach ($wordsArray as $key => $value) {
		$tmpLocator += strpos(substr($string, $tmpLocator),$value);
		if (strcmp('(',substr($value, 0,1))==0){
			$parenthesisFlag = true;
			array_push($resultsArray,parenthesis(explode(')', substr($string, $tmpLocator))));
		}
		else{
			$tmpArray = explode(' ', $value);
			foreach ($tmpArray as $word) {
				if ($parenthesisFlag){
					if (strcmp(')',substr($value,strlen($value)-1)) != 0)
						break;
					$parenthesisFlag = false;
				}
				if (in_array($word,array('-','+','|'))){
					array_push($resultsArray,$word);
				}
				else{
					if (!in_array($word, $stopWords)){
						foreach ($GLOBALS['index']['index'] as $indexWord => $positions) {
							if (strcmp($indexWord,$word)==0){
								foreach ($positions['locations'] as $locations){
									$locationsArray = array();
									$arr = explode(',', $locations);
									if (!isset($locationsArray[$arr[0]]))
										$locationsArray[$arr[0]] = array();
									array_push($locationsArray[$arr[0]],$arr[1].','.$arr[2]);
								}
								array_push($resultsArray,$locationsArray);
								break;
							}
						}
					}
					else{
						array_push($resultsArray,array());
					}
				}
			}
		}
	}

	echo '<pre>';
	print_r($resultsArray);
	echo '</pre>';

	$returnArray = array();
	$andFlag = false;
	$notFlag = false;
	$orFlag = false;
	foreach ($resultsArray as $key => $value) {
		if ($andFlag){
			if (is_array($value) && count($value)>0){
				print_r($returnArray);
				print_r($value);
				foreach ($returnArray as $file => $locations) {
					if ($notFlag){
						if (array_key_exists($file, $value)){
							unset($returnArray[$file]);
						}
						$notFlag = false;
					}
					else{
						if (!array_key_exists($file, $value)){
							unset($returnArray[$file]);
						}
						else{
							foreach ($value as $innerkey => $positions) {
								array_push($returnArray[$file],$positions);
							}
						}
					}
				}
			}
			$andFlag = false;
			continue;
		}

		if ($orFlag){
			if (is_array($value)){
				foreach ($value as $key => $files) {
					foreach ($files as $file => $locations) {
						if (!$notFlag){
							if (!array_key_exists($key, $returnArray))
								$returnArray[$key] = array();
							array_push($returnArray[$key],$locations);	
						}
					}
				}
			}	
			$orFlag = false;
			continue;
		}

		if (is_array($value)){
			foreach ($value as $key => $files) {
				foreach ($files as $file => $locations) {
					if (!isset($returnArray[$file]))
						$returnArray[$key] = array();
					array_push($returnArray[$key],$locations);
				}
			}
		}
		else{
			if (strcmp($value,'+')==0){
				$andFlag = true;
				echo 'AND';
			}

			if (strcmp($value,'|')==0){
				$orFlag = true;
				echo 'OR';
			}

			if (strcmp($value,'-')==0){
				echo 'NOT';
				if ($notflag)
					$notFlag = false;
				else
					$notFlag = true;
			}
		}
	}
	echo '<pre>';
	print_r($returnArray);
	echo '</pre>';


	function parenthesis($arr){
		$array = explode(' ', $arr[0]);
		/*echo '<pre>';
		print_r($array);
		echo '</pre>';*/
		$tempArray = array();
		$orFlag = false;
		$andFlag = false;
		//Params for NOT
		$notFlag = false;
		$notLocation = 0;
		$notCounter = -1; //In case of inner parenthesis
		$array[0] = substr($array[0], 1);
		foreach ($array as $key => $value) {
			if (strcmp('(',substr($value, 0,1))==0){
				$string = implode(' ', $array);
				$parenthesisArr = parenthesis(explode(')', substr($string, strpos(0, 1))));
				foreach ($parenthesisArr as $file => $locations){
					foreach ($locations as $keyArr => $valueArr) {
						$counter+=1;
						array_push($tempArray,$file.','.$valueArr);
					}
				}
				$value = '';
			}

			if ($notFlag && $notLocation==-1){
				if ($notCounter>0)
					$notLocation = count($tempArray)-$notCounter-1;
				else
					$notLocation = count($tempArray);
			}

			if (strcmp('+',$value)==0){
				$andFlag = true;
			}

			else if (strcmp('|',$value)==0){
				$orFlag = true;
			}

			else if (strcmp('-',$value)==0){
				$notFlag = true;
			}

			else{
				if ((!in_array($value, $GLOBALS['stopWords'])) && (!in_array($value, array('-','+','-')))){
					foreach ($GLOBALS['index']['index'] as $word => $positions) {
						if (strcmp($value,$word)==0){
							array_push($tempArray,$positions['locations']);
							break;
						}
					}
				}
			}
		}
		$words = array();
		foreach ($tempArray as $tmpKey => $tmpArray) {
			$words[$tmpKey] = array();
			foreach ($tmpArray as $key => $value){
				$locations = explode(',',$value);
				if (!isset($words[$tmpKey][$locations[0]])){
					$words[$tmpKey][$locations[0]] = array();
				}
				array_push($words[$tmpKey][$locations[0]],$locations[1].','.$locations[2]);
			}
			
		}

		if ($andFlag){	
			foreach ($words[0] as $key => $value) {
				for ($i=1; $i <count($words) ; $i++) {
					if ($notFlag){
						if (array_key_exists($key, $words[$i])){
							unset($words[0][$key]);
						}
					}
					else{
						if (!array_key_exists($key, $words[$i])){
							unset($words[0][$key]);
						}
						else{
							foreach ($words[$i][$key] as $innerkey => $value) {
								array_push($words[0][$key],$value);
							}
						}
					}
				}
			}
		}

		if ($orFlag){
			for ($i=1; $i < count($words); $i++) { 
				foreach ($words[$i] as $key => $value) {
					if (!$notFlag){
						if (!array_key_exists($key, $words[0]))
							$words[0][$key] = array();
						foreach ($words[$i][$key] as $innerkey => $value) {
							array_push($words[0][$key],$value);
						}
					}
				}
			}
		}
		return($words[0]);
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
					if ($seFquenceArray[$seq][$key] < $seq){
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
?>