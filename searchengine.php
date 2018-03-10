<?php
	$string = $_GET['search'];
	$fpIndex = fopen('db/index.json', 'r+');
	$index = (array)json_decode(fread($fpIndex, filesize('db/index.json')),true);
	$string = str_replace(array('.',';',',',':','"',"'",'!','?','/','\n','\r','\n\r','','`'), '', $string);
	$string = trim($string);
	$wordsArr = explode('(', $string);
	$andArray = array();
	$orArray = array();
	$wordArray = array();
	$notArray = array();
	foreach ($wordsArr as $words){
		$words = trim($words);
		$tmpArray = explode(')',$words);
		foreach ($tmpArray as $key => $value) {
			$value = trim($value);
			/*if (strpos($value, '+')<strlen($value))
				array_push($andArray,$tmpArray[$key-1]);
			else if()*/
			array_push($wordArray,explode(' ', $value));
		}
	}
	$wordsArray = array();
	$toSearchArray = array();
	$orFlag = false;
	$notFlag = false;
	$andFlag = false;
	foreach ($wordArray as $words) {
		print_r($words);
		foreach ($words as $key => $value) {
			$value = trim($value);
			if($andFlag){
				array_push($andArray,$value);
				array_push($toSearchArray, $andArray);
				$andArray = array();
				$andFlag = false;
			}

			elseif($orFlag){
				array_push($orArray,$value);
				array_push($toSearchArray, $orArray);
				$orArray = array();
				$orFlag = false;
			}

			elseif($notFlag){
				array_push($notArray,$value);
				$notFlag = false;
			}
			if (strcmp($value, '+')==0){
				array_push($andArray,$wordsArray[count($wordsArray)-1].' + ');
				$andFlag = true;
				continue;
			}
			if (strcmp($value, '|')==0){
				array_push($orArray,$wordsArray[count($wordsArray)-1].' | ');
				$orFlag = true;
				continue;
			}
			if (strcmp($value, '-')==0){
				$andFlag = true;		
				continue;	
			}
			array_push($wordsArray,$value);
		}
	}
	echo "<pre>";
	print_r($wordsArray);
	echo "</pre>";
	echo "<pre>";
	print_r($toSearchArray);
	echo "</pre>";
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
	}*/
	fclose($fpIndex);
?>

<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
		<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
		<script src="includes/script.js"></script>
		<link rel="stylesheet" href="includes/style.css">
		<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
	</head>
	<body>
		<header class="top">
			<div class="container">
			  <div class="row">
			    <div class="col-xs-4">
			    </div>
			    <div class="searchbuttons col-xs-4 text-center">
			        <button id='and'>AND</button>
					<button id='or'>OR</button>
					<button id='not'>NOT</button>
					<div>
						<form action="searchengine.php" method="GET">
							<input type="text" name="search" value="<?php echo $string;?>">
							<input type="submit" name="submit">
						</form>
					</div>
			    </div>
			  </div>
			</div>
		</header>
		<main>
		<div class="container">
			<div class="row">
			    <div class="col-l-2">
			    </div>
			    <div class="col-l-2 text-center">
			    <?php
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
			    ?>
			    </div>
			</div>
		</div>
		</main>
	</body>
</html>