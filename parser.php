<?php
	$fpIndex = fopen('db/index.json', 'r+');
	$index = (array)json_decode(fread($fpIndex, filesize('db/index.json')),true);
	$fpStopwords = fopen('db/stopwords.json', 'r+');
	$stopWords = json_decode(fread($fpStopwords, filesize('db/stopwords.json')),true);
	fclose($fpStopwords);
	$author = $_POST['author'];
	$filename = $_POST['filename'].'.txt';
	//We'll check if the filename exists on our DB
	$inArray = false;
	foreach ($index['files'] as $key => $value) {
		if (strcmp($filename,$value['name'])==0){
			$inArray=true;
			break;
		}
	}
	if (!$inArray){
		$filenumber = count($index['files'])+1;
		$fpFile = fopen('toparse/'.$filename, 'r');
		$fArr = array();
		while (!feof($fpFile)){
			$textToAnalyze = strtolower(trim(fgets($fpFile)));
			if (ord($textToAnalyze)>0){
				$textToAnalyze = str_replace(array('.',';',',',':','"',"'",'!','?','/','\n','\r','\n\r','','`','+','-','$','%','&','*'), '', $textToAnalyze);
				$textToAnalyze = trim($textToAnalyze);
				//We will compare the array of words to the array of stop words and return only the words that are not stop words
				array_push($fArr, array_diff(explode(' ', $textToAnalyze),$stopWords));
			}
		}
		foreach ($fArr as $key => $value) {
			foreach ($value as $num => $word) {
				$word = str_replace(array('(',')','[',']'),'',$word);
				if (strcmp($word, ' ')!=0){
					if (!array_key_exists($word, $index['index'])){
						$index['index'][$word] = array($filenumber.','.$key.','.$num);
					}
					else {
						array_push($index['index'][$word],$filenumber.','.$key.','.$num);
					}
				}
			}
		}
		rewind($fpIndex);
		rewind($fpFile);
		$fpNewFile = fopen('db/'.$filenumber.'.txt','w');
		$index['files'][$filenumber] = array('name' => $filename,'author' => $author);
		ksort($index['index']);
		fwrite($fpIndex, json_encode($index));
		fwrite($fpNewFile, fread($fpFile,filesize('toparse/'.$filename)));
		fclose($fpFile);
		fclose($fpNewFile);
	}
	fclose($fpIndex);
	echo "Success";
?>