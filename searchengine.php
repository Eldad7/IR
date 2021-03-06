<?php
	$string = isset($_POST['search']) ? $_POST['search'] : '';
	$unq = isset($_POST['unq']) ? $_POST['unq'] : '';
	$locator = isset($_POST['locator']) ? $_POST['locator'] : 0;

	//Array of results to return
	$returnArray = array();
	$oldSearch = false;
	//Open recent searches to see if it exists
	$fpRecentSearches = fopen('db/recentsearches.json','r+');
	$recentSearches = (array)json_decode(fread($fpRecentSearches, filesize('db/recentsearches.json')),true);
	fclose($fpRecentSearches);
	if ($unq!=''){
		foreach ($recentSearches as $key => $value) {
			if (in_array($unq, $value['unq'])){
				$results = $recentSearches[$key]['json'];
				$fpRecentSearches = fopen('db/recentsearches.json','r+');
				fwrite($fpRecentSearches, json_encode($recentSearches));
				fclose($fpRecentSearches);
				echo json_encode(array('unq' => $unq, 'json' => array_slice($results, $locator,$locator+10)));
				die();
			}
		}
		
		$oldSearch = true;
	}
	//Check top searches, if doesn't exist we go the search in index
	$fpTopSearches = fopen('db/topsearches.json','r+');
	$topSearches = (array)json_decode(fread($fpTopSearches, filesize('db/topsearches.json')),true);
	fclose($fpTopSearches);
	$bestMatch = 10;
	//We will use levenshtein algorithm to compare strings
	foreach (array_slice($topSearches, 0,1000) as $key => $search){
		$match = abs(levenshtein($string, $search['search'])); 
		if ($match==0){
			if (count($search['results'])>0){
				$returnArray = $search['results'];
				$topSearches[$key]['hits']++;
				$bestMatch = $match;
				break;
			}
			else{
				continue;
			}
		}
		//Match result from string compare
		if ($match<$bestMatch){
			$GLOBALS['topResult'] = $key;
		}
		$bestMatch = $match;
	}
	if (empty($returnArray) && isset($topResult)){
		$closestResult = $topSearches[$topResult];
	}
	//Open Index and stop words
	$fpIndex = fopen('db/index.json', 'r+');
	$index = (array)json_decode(fread($fpIndex, filesize('db/index.json')),true);
	fclose($fpIndex);
	//Sort index by Hits
	arsort($index['index']);
	$fpStopWords = fopen('db/stopwords.json', 'r');
	$stopWords = (array)json_decode(fread($fpStopWords, filesize('db/stopwords.json')),true);
	fclose($fpStopWords);
	//Remove unsearchable characters for index search
	$string = str_replace(array('.',';',',',':',"'",'!','?','/','\n','\r','\n\r','','`','[',']','{','}'), '', $string);
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
			$value = str_replace(array('(',')','"'), '', $value);
			$wordHits[$value] = array();
		}
		unset($wordHits[$key]);
	}
	
	foreach ($wordsArr as $key => $value) {
		//For each sentence - we will remove whitespaces and sent it to the calculate function, as words (explode)
		calculate($value);
	}

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
						if (isset($resultsArray[$key-1][$file])){
							if (!array_key_exists($file, $returnArray))
								$returnArray[$file] = array();
							foreach ($resultsArray[$key-1][$file] as $location)
								array_push($returnArray[$file],$locations);
						}
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
					if (!isset($returnArray[$innerKey]))
						$returnArray[$innerKey] = array();
					array_push($returnArray[$innerKey],$locations);
				}
			}
		}
		//Raise flags if there is a sign - + |
		if ($key+1<count($resultsArray)){
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
	}

	//Remove duplicates
	foreach ($returnArray as $key => $value) {
		$returnArray[$key] = array_unique($value);
	}

	//In case no results from original search
	if (isset($closestResult) && empty($returnArray)){
		$closest = 'true';
		$returnArray = $closestResult['results'];
	}
	else
		$closest = 'false';

	//Now we create the json to return
	//We check if what we searched for is a name of a song - if it is we will put it at the top. If not we will put in the one with the largest number of hits

	foreach ($index['files'] as $key => $value) {
		if (levenshtein($string, $value['name'])<2){
			$returnArray[$key]['rank'] = 999999;
			$index['files'][$key]['hits']++;	
		}
		if ($value['hits']<1000)
			break;
	}

	foreach ($returnArray as $file => $hits) {
		if (!isset($file['rank']))
			$returnArray[$file]['rank'] = rank($file);
	}

	uasort($returnArray, function($a, $b){return $b['rank'] - $a['rank'];});

	foreach ($returnArray as $key => $value) {
		unset($returnArray[$key]['rank']);
	}

	$counter = 0;
	$jsonToSend = array();
	foreach ($returnArray as $key => $value) {
		array_push($jsonToSend,array("href" => $key,'fileName' => $index['files'][$key]['name'], 'author' => $index['files'][$key]['author'], 'preview' => $index['files'][$key]['preview']));
		if (++$counter==10){
			break;
		}
	}

	//Now we update recent searches and top searches
	$fpTopSearches = fopen('db/topsearches.json','w+');
	if ($bestMatch != 0 && $closest!=='true'){   		
		array_push($topSearches, array("search" => $_POST['search'],"hits" => 1,"results" => $returnArray));			
	}
	fwrite($fpTopSearches, json_encode($topSearches));
	fclose($fpTopSearches);
	//Recent searches
	$unq = md5(microtime(true));

	$fpRecentSearches = fopen('db/recentsearches.json','w+');
	//$recentSearches = (array)json_decode(fread($fpRecentSearches, filesize('db/recentsearches.json')),true);
	$existingSearch = false;
	foreach ($recentSearches as $key => $value) {
		if ($_POST['search'] == $value['search']){
			array_push($recentSearches[$key]['unq'],$unq);
			$recentSearches[$key]['search'] = $_POST['search'];
			$recentSearches[$key]['expire'] = strtotime("+20 minutes");
			$existingSearch = true;
		}
	}
	if (!$existingSearch && $closest!='true') {
		$recentSearches[count($recentSearches)]['unq'] = array($unq);
		$recentSearches[count($recentSearches)]['json'] = $jsonToSend;
		$recentSearches[count($recentSearches)]['expire'] = strtotime("+20 minutes");
		$recentSearches[count($recentSearches)]['search'] = $_POST['search'];
	}
	fwrite($fpRecentSearches, json_encode($recentSearches));
	fclose($fpRecentSearches);

	if ($closest === 'true')
		$closestQuery = $closestResult['search'];
	else{
		$closestQuery = $_POST['search'];
	}
	//return results
	echo json_encode(array('unq' => $unq, 'json' => $jsonToSend,'totalResults' => count($returnArray),"closest" => $closestQuery));

	function rank($file){
		$totalHits = 0;
		$fileCounter = 0;
		foreach ($GLOBALS['wordHits'] as $word => $files) {
			if (array_key_exists($file, $files)){
				$fileCounter+=1;
				$totalHits+=$files[$file];
			}
		}
		return ($fileCounter/count($GLOBALS['wordHits'])) * $totalHits;
	}

	function calculate($string){
		$arr = explode(' ', trim($string));
		$tempArray = array();
		$apostropheFlag = false;
		$apostropheArr = array();
		$andFlag = false;
		//Params for NOT
		$notFlag = false;
		//For each word in the combination, we remove end of parenthesis if it contains it
		foreach ($arr as $key => $value) {
			$value = strtolower(str_replace(')', '', $value));
			//If we have a sign (+ - |) and it is not at the end of the combination (FIX for nested parenthesis) - we push the relevant flag to the results array for later use
			if (strpos($value, '"') || substr($value, 0,1)=='"' || $apostropheFlag){
				array_push($apostropheArr, $value);
				
				if (!$apostropheFlag){
					$apostropheFlag=true;
					continue;
				}
				else{
					if (strpos($value, '"') || substr($value, 0,1)=='"'){
						$apostropheFlag = false;
						array_push($GLOBALS['resultsArray'],apostropheCalculation($apostropheArr));
					}
				}
			}

			else if (strcmp('+',$value)==0){
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
					$found = false;
					foreach ($GLOBALS['index']['index'] as $word => $positions) {
						if (strcmp($value,$word)==0){
							//If found - we push results
							array_push($tempArray,array($positions['locations'],$value));
							$found = true;
							break;
						}
						//If we haven't found the word - return empty array
					}
					if (!$found){
						array_push($tempArray, array(array(),$value));
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
					if ($GLOBALS['index']['files'][$locations[0]]['hidden']!=1){
						if (!isset($words[$tmpKey][$locations[0]])){
							$words[$tmpKey][$locations[0]] = array();
						}
						array_push($words[$tmpKey][$locations[0]],$locations[1].','.$locations[2]);
					}
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
					if (!isset($GLOBALS['wordHits'][$files['Word']][$key])){
						$GLOBALS['wordHits'][$files['Word']][$key] = 0;
					}
					$GLOBALS['wordHits'][$files['Word']][$key]+=count($value);
				}
			}
		}
		//We push the results into the global results array, and if we had a flag - we push it after (FIX for nested parenthesis - to keep the order)
		array_push($GLOBALS['resultsArray'],$resultArray);
		if (isset($flagToPush))
			array_push($GLOBALS['resultsArray'],$flagToPush);
	}

	function apostropheCalculation($arr){
		$tempArray = array();
		$apostropheArray = array();
		foreach ($arr as $key => $value) {
			$value = str_replace('"', '', $value);
			if (!in_array($value, array('-','+','-'))) {
				//If it's not in stop words - we search the index
				$found = false;
				foreach ($GLOBALS['index']['index'] as $word => $positions) {

					if (strcmp($value,$word)==0){
						//If found - we push results
						array_push($tempArray,array($positions['locations'],$value));
						$found = true;
						break;
					}
					//If we haven't found the word - return empty array
				}
				if (!$found){
					array_push($tempArray, array(array(),$value));
				}
			}
		}
		foreach ($tempArray as $tmpKey => $tmpArray) {
			if (is_array($tmpArray)){
				$apostropheArray[$tmpKey] = array();
				foreach ($tmpArray[0] as $key => $value){
					$locations = explode(',', $value);
					if ($GLOBALS['index']['files'][$locations[0]]['hidden']!=1){
						array_push($apostropheArray[$tmpKey],$value);
					}
				}
			}
			else
				//Sign - + - |
				return array();
		}

		$resultArray = array();
		for ($i=count($apostropheArray)-1; $i > 0 ; $i--) { 
			$tmpArray = array();
		 	foreach ($apostropheArray[$i] as $locations) {
		 		$tmpLocations = explode(',', $locations);
		 		foreach ($apostropheArray[$i-1] as $key => $value) {
			 		$secondLocations = explode(',', $value);
			 		if ($secondLocations[0] == $tmpLocations[0]){
			 			array_push($tmpArray,$value);
			 		}
			 	}
		 	}
		 	$tmpArray = array_unique($tmpArray);
		 	foreach ($apostropheArray[$i] as $locations) {
		 		$tmpLocations = explode(',', $locations);
			 	foreach ($tmpArray as $key => $value) {
			 		$secondLocations = explode(',', $value);
			 		if ($tmpLocations[1] == $secondLocations[1] && $tmpLocations[0] == $secondLocations[0]){
			 			if ($tmpLocations[2] == $secondLocations[2]+1){
			 				array_push($resultArray,$locations);
			 				array_push($resultArray,$value);
			 			}
			 		}
			 		if ($tmpLocations[1] == $secondLocations[1]-1 && $tmpLocations[2] == 0 && $tmpLocations[0] == $secondLocations[0]){
							array_push($resultArray,$locations);
			 				array_push($resultArray,$value);
			 		}

			 	}
			 }
			 		
		 }
		 $resultArray = array_unique($resultArray);
		 sort($resultArray);
		 $returnArray = array();

		 $counter = count($arr)-1;
		 for ($i=0; $i < count($resultArray); $i++, $counter--) { 
		 	if ($counter==0){
		 		array_push($returnArray,array_slice($resultArray, $i-count($arr)+1,$i+1));
		 		$counter = count($arr);
		 	}
		 }
		 $resultArray = array();
		 for ($i=0; $i<count($returnArray); $i++){
		 	$tmpFile = substr($returnArray[$i][0], 0,1);
		 	if (!isset($resultArray[$tmpFile]))
		 		$resultArray[$tmpFile] = array();
		 	foreach ($arr as $key => $value) {
		 		if (!isset($GLOBALS['wordHits'][$value][$tmpFile] )){
					$GLOBALS['wordHits'][$value][$tmpFile] = 0;
				}
				$GLOBALS['wordHits'][$value][$tmpFile]++;
		 	}
		 	foreach ($returnArray[$i] as $lkey => $location) {
		 		array_push($resultArray[$tmpFile],$location);
		 	}
		}
		return $resultArray;
	}
?>