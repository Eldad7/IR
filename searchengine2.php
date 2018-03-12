<?php
	try{
		//$queryString = $_GET['search'];
		$string = 'like the + (better + plea) + (better | lassie)';
		echo '<pre>'.$string.'</pre>';
		$fpIndex = fopen('db/index.json', 'r+');
		$index = (array)json_decode(fread($fpIndex, filesize('db/index.json')),true);
		fclose($fpIndex);
		$fpStopWords = fopen('db/stopwords.json', 'r+');
		$stopWords = (array)json_decode(fread($fpStopWords, filesize('db/stopwords.json')),true);
		fclose($fpStopWords);
		$string = str_replace(array('.',';',',',':','"',"'",'!','?','/','\n','\r','\n\r','','`'), '', $string);
		$string = trim($string);
		$toSearchArray = array();
		$resultsArray = array();
		$andFlag = false;
		$orFlag = false;
		$notFlag = false;
		$notArray = array();
		if (strpos($string, '(') || strcmp('(',substr($string, 0,1))==0){
			while (strpos($string, ')')){
				echo $string.'<br/>';
				$tempString = substr($string, strrpos($string, '('),strpos($string,')')+1);
				echo $tempString.'<br/>';
				array_push($toSearchArray, $tempString);
				$string = str_replace($tempString, '',$string);
			}
		}
		else{
			$wordsArray = explode(' ', $string);
		}

		echo '<pre>';
		print_r($toSearchArray);
		echo '</pre>';
		/*echo '<pre>';
		print_r($wordsArray);
		echo '</pre>';*/
		/*foreach ($wordsArray as $key => $value) {

			if (strcmp('+',$value)==0){
				if ($key == 0)
					continue;
				else{
					if ($key+1>count($wordsArray))
						continue;
					$tempString = '('.$wordsArray[$key-1].' '.$value.' '.$wordsArray[$key+1];
					$key++;
					while ($key+1<count($wordsArray) && $key+1=='+'){
						$key++;
						if ($key+1>count($wordsArray))
							break;
						$tempString.=' + '.$wordsArray[$key+1];
					}
					$tempString.=')';
					array_push($toSearchArray,$tempString);
				}
			}

			else if (strcmp('|',$value)==0){
				if ($key == 0)
					continue;
				else{
					if ($key+1>count($wordsArray))
						continue;
					$tempString = '('.$wordsArray[$key-1].' '.$value.' '.$wordsArray[$key+1];
					$key++;
					while ($key+1<count($wordsArray) && $key+1=='+'){
						$key++;
						if ($key+1>count($wordsArray))
							break;
						$tempString.=' + '.$wordsArray[$key+1];
					}
					$tempString.=')';
					array_push($toSearchArray,$tempString);
				}
			}

			else if (strcmp('-',$value)==0){
				$notFlag = true;
			}
		}

		foreach ($toSearchArray as $key => $value) {
			array_push($resultsArray,parenthesis(explode(' ', substr($string, $tmpLocator))));
			if (count($resultsArray)>1){
				foreach ($resultsArray as $key => $value) {
					array_merge_recursive($resultsArray[0],$resultsArray[$key]);
				}
			}
		}


		function calculate($arr){
			$array = explode(' ', $arr[0]);
			echo '<pre>';
			print_r($array);
			echo '</pre>';
			$tempArray = array();
			$orFlag = false;
			$andFlag = false;
			//Params for NOT
			$notFlag = false;
			$notLocation = 0;
			$notCounter = -1; //In case of inner parenthesis
			$array[0] = ltrim($array[0]);
			foreach ($array as $key => $value) {
				if (strcmp('(',substr($value, 0,1))==0){
					$string = implode(' ', $array);
					$parenthesisArr = parenthesis(explode(')', substr($string, $tmpLocator)));
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
					if (!in_array($value, $stopWords) && (!$notFlag && !$orFlag && !$andFlag)){
						foreach ($GLOBALS['index']['index'] as $word => $positions) {
							if (strcmp($value,$word)==0){
								array_push($tempArray,$positions['locations']);
								break;
							}
						}
					}
				}


				//unset($GLOBALS['wordsArray'][$key]);
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
			echo '<pre>';
			print_r($words[0]);
			echo '</pre>';
			return($words[0]);
		}*/
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
	}
	catch (Exception $e){
		echo $e->getMessage();
	}
?>