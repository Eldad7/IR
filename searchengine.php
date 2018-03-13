<?php
	$string = isset($_GET['search']) ? $_GET['search'] : '';
	//echo '<pre>'.$string.'</pre>';
	//Open Index and stop words
	$fpIndex = fopen('db/index.json', 'r+');
	$index = (array)json_decode(fread($fpIndex, filesize('db/index.json')),true);
	fclose($fpIndex);
	//Sort index by Hits
	arsort($index['index']);
	$fpStopWords = fopen('db/stopwords.json', 'r+');
	$stopWords = (array)json_decode(fread($fpStopWords, filesize('db/stopwords.json')),true);
	fclose($fpStopWords);
	//Remove unsearchable characters for index search
	$string = str_replace(array('.',';',',',':','"',"'",'!','?','/','\n','\r','\n\r','','`','[',']','{','}'), '', $string);
	//Remove whitespaces
	$string = trim($string);
	//Create an array of as many sentences as there are parenthesis
	$wordsArr = explode('(', $string);
	$resultsArray = array();
	//Wordshits will hold all the words of the search and which files contain them and how many times
	$wordHits = explode(' ', $string);
	$wordHits = array_unique($wordHits);
	foreach ($wordHits as $key => $value) {
		if (!in_array($value, array('+','-','|'))){
			$value = str_replace(array('(',')'), '', $value);
			$wordHits[$value] = array();
		}
		unset($wordHits[$key]);
	}
	
	foreach ($wordsArr as $key => $value) {
		//For each sentence - we will remove whitespaces and sent it to the calculate function, as words (explode)
		calculate(explode(' ', trim($value)));
	}

	//Array of results to return
	$returnArray = array();
	//Behavioral flags
	$andFlag = false;
	$notFlag = false;
	$orFlag = false;

	//ParenthesisFlag will be used for calculations inside results. If it is raised then we calculate only two arrays at a time
	$parenthesisFlag = false;
	foreach ($resultsArray as $key => $value) {
		//For explaining AND and OR, let's say $resultsArray[$key-1] is A and $resultsArray[$key+1] is B
		//If flag is raised - we are calculating parenthesis and we need not check again the same array
		//It is true for nested parenthesis since we are breaking it down in results array
		if ($parenthesisFlag) {
			$parenthesisFlag = false;
			continue;
		}
		//If any of the flags exists - we will compare the next array to the previous one and then push it to return array
		if ($andFlag){
			//If we have not reached the end of the array
			if ($key+1<count($resultsArray)){
				if (is_array($resultsArray[$key+1]) && count($value)>0){
					foreach ($resultsArray[$key-1] as $file => $locations) {
						
						//We are iterating on A. We split this into two cases, if it is and not, or just and
						//If it is and not - we check array B to see if it contains elements from A, if it does - we remove it
						//If it's just and - we check array B to see if it contains elements from A, if it doesn't - we remove it
						//If it does - we add it to A
						//Eventually we push the results to returnArray
						if ($notFlag){
							if (array_key_exists($file, $resultsArray[$key+1])){
								unset($resultsArray[$key-1][$file]);
							}
							$notFlag = false;
						}
						else{
							if (!array_key_exists($file, $resultsArray[$key+1])){
								unset($resultsArray[$key-1][$file]);
							}
							else{
								foreach ($resultsArray[$key+1][$file] as $innerFile => $positions) {
									array_push($resultsArray[$key-1][$file],$positions);
								}
							}
						}
						if (!array_key_exists($file, $returnArray))
							$returnArray[$file] = array();
						foreach ($resultsArray[$key-1][$innerKey] as $location)
							array_push($returnArray[$file],$location);
					}
				}
			}
			$andFlag = false;
			$parenthesisFlag = true;
			continue;
		}



		if ($orFlag){
			//Or not is just like OR so we are not taking that into account
			//We are checking to see if is NOT OR. If it is just OR - we check B to see if A contains elements
			//Eventually we are adding B to A 
			if (is_array($resultsArray[$key+1])){
				foreach ($resultsArray[$key+1] as $innerKey => $files) {
					foreach ($files as $file => $locations) {
						if (!$notFlag){
							if (!array_key_exists($innerKey, $resultsArray[$key-1]))
								$resultsArray[$key-1][$innerKey] = array();
							array_push($resultsArray[$key-1][$innerKey],$locations);	
						}
					}
					if (!array_key_exists($innerKey, $returnArray))
							$returnArray[$innerKey] = array();
					foreach ($resultsArray[$key-1][$innerKey] as $location)
						array_push($returnArray[$innerKey],$location);
				}
			}	
			$parenthesisFlag = true;
			$orFlag = false;
			continue;
		}

		//For regular calculations - if returnArray contains the file - append the locations. If not - create and add locations
		if (is_array($value)){
			foreach ($value as $innerKey => $files) {
				foreach ($files as $file => $locations) {
					if (!isset($returnArray[$file]))
						$returnArray[$innerKey] = array();
					array_push($returnArray[$innerKey],$locations);
				}
			}
		}
		//Raise flags if there is a sign - + |
		if (!is_array($resultsArray[$key+1])){
			if (strcmp($resultsArray[$key+1],'+')==0){
				$andFlag = true;
			}

			if (strcmp($resultsArray[$key+1],'|')==0){
				$orFlag = true;
			}

			if (strcmp($resultsArray[$key+1],'-')==0){
				if ($notflag)
					$notFlag = false;
				else
					$notFlag = true;
			}
		}
	}

	//Remove duplicates
	foreach ($returnArray as $key => $value) {
		$returnArray[$key] = array_unique($value);
	}
	echo '<pre>';
	print_r($returnArray);
	echo '</pre>';

	echo '<pre>';
	print_r($wordHits);
	echo '</pre>';


	function calculate($arr){
		$tempArray = array();
		$andFlag = false;
		//Params for NOT
		$notFlag = false;
		//For each word in the combination, we remove end of parenthesis if it contains it
		foreach ($arr as $key => $value) {
			$value = str_replace(')', '', $value);
			//If we have a sign (+ - |) and it is not at the end of the combination (FIX for nested parenthesis) - we push the relevant flag to the results array for later use
			if (strcmp('+',$value)==0){
				if ($key+1==count($arr)){
					$flagToPush = $value;
				}
				else
					array_push($tempArray,$value);
			}

			else if (strcmp('|',$value)==0){

				if (($key+1)==count($arr))
					$flagToPush = $value;
				else
					array_push($tempArray,$value);
			}

			else if (strcmp('-',$value)==0){
				if (($key+1)==count($arr))
					$flagToPush = $value;
				else
					array_push($tempArray,$value);
			}

			//If this is a word we search the index and stopWords to make sure if we need to search it (stopwords first)
			else{
				if ((!in_array($value, $GLOBALS['stopWords'])) && (!in_array($value, array('-','+','-')))){
					//If it's not in stop words - we search the index
					foreach ($GLOBALS['index']['index'] as $word => $positions) {
						if (strcmp($value,$word)==0){
							//If found - we push results
							array_push($tempArray,array($positions['locations'],$value));
							break;
						}
					}
				}
			}
		}
		$words = array();
		//For each result from earlier loop we check if it is an array or a sign
		//If it is an array - we break down the value (locations - file, line, offset), create a position for the file and push the locations, so we will have an array with files as keys and locations as values
		foreach ($tempArray as $tmpKey => $tmpArray) {
			if (is_array($tmpArray)){
				$words[$tmpKey] = array();
				$words[$tmpKey]['Word'] = $tmpArray[1];
				foreach ($tmpArray[0] as $key => $value){
					$locations = explode(',',$value);
					if (!isset($words[$tmpKey][$locations[0]])){
						$words[$tmpKey][$locations[0]] = array();
					}
					array_push($words[$tmpKey][$locations[0]],$locations[1].','.$locations[2]);
				}
			}
			else
				//Sign - + - |
				array_push($words,$tmpArray);
		}
		//Results array is the final array
		//We got over it and do same calculations as earlier
		$resultArray = array();
		foreach ($words as $files){
			if (!is_array($files)){
				if ($files == '+')
					$andFlag = true;
				if ($files == '-')
					$notFlag = true;
				continue;
			}
			//If we have and - we check to see if it's AND NOT or AND and set the results
			if ($andFlag){	
				if (count($words)>0){
					foreach ($resultArray as $key => $value) {
						if ($notFlag){
							if (array_key_exists($key, $files)){
								unset($resultArray[$key]);
							}
						}
						else{
							if (!array_key_exists($key, $files)){
								unset($resultArray[$key]);
							}
							else{
								foreach ($files as $innerkey => $value) {
									array_push($resultArray[$key],$value);
								}
							}
						}					
					}
				}
				$andFlag = false;
			}
			//If it is OR or regular search
			else{
				foreach ($files as $key => $file) {
					if (is_numeric($key)){
						if (!$notFlag){
							if (!array_key_exists($key, $resultArray))
								$resultArray[$key] = array();
							foreach ($files[$key] as $innerkey => $value) {
								array_push($resultArray[$key],$value);
							}
						}
					}
				}
			} 

			if ($notFlag)
				$notFlag = false;

			//Final loop on these hits - we update the wordHits array with the files that contains it and how many times
			foreach ($files as $key => $value) {
				if (is_numeric($key)){
					if (!isset($GLOBALS['wordHits'][$files['Word']][$key]))
						$GLOBALS['wordHits'][$files['Word']][$key] = 0;
					$GLOBALS['wordHits'][$files['Word']][$key]++;
				}
			}
		}

		//We push the results into the global results array, and if we had a flag - we push it after (FIX for nested parenthesis - to keep the order)
		array_push($GLOBALS['resultsArray'],$resultArray);
		if (isset($flagToPush))
			array_push($GLOBALS['resultsArray'],$flagToPush);
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