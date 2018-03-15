var query = null, queryString = [], unqIdentifier = null, resultsLocator=0, counter=1;

$(document).ready(function(){
	//Get search parameter and sent to server
	location.search
        .substr(1)
        .split("&")
        .forEach(function (item) {
          queryString = item.split("=");
          
          if (queryString[0] === 'search'){
          	queryString[1] = queryString[1].replace(/\+/g,' ');
          	query = decodeURIComponent(queryString[1]);
          }
        });
    $( "input[name=search]" ).val(query);
	$('#or').on('click', function(){
		$('#search').val($('#search').val()+' | ');
	});
	$('#and').on('click', function(){
		$('#search').val($('#search').val()+' + ');
	});
	$('#not').on('click', function(){
		$('#search').val($('#search').val()+' - ');
	});
	$.ajax({
		type:"POST",
		url:'searchengine.php',
		data:{search:query},
		success:function(data){
			console.log(data);
			results = JSON.parse(data);
			console.log(results);
			unqIdentifier = results.unq;
			var html = '<center>';
			if (results.closest!=query)
				html+='<h3>Showing results for' + results.closest + '</h3>';
			
			html+='<div id="results"><div class="row-md-4">';
			for (counter = resultsLocator; counter<results.json.length || ((counter%10==0 && counter!=0) && counter>0); counter++){
				html+='<div><h1><a href = "' + results.json[counter].href + '" target=_blank>' + results.json[counter].fileName + '</a></h1>';
	    		html+="<h6>By " + results.json[counter].author + "</h6>";
	    		html+='<h4>' + results.json[counter].preview +'</h4></div></div>';
			}
			html+='<ul>';
			for (var i=0, counter=1; i<results.totalResults && i<110; i++, counter++){
				html+='<li id="'+(i*10)+'">'+counter+'</li>';
				i+=10;
			}
			html+='</ul></div></center>';
			$('main').html(html);
			$('li').find('#0').attr('font-weight','bold');
			$('li').bind('click', function(da){
				console.log(event.target);
				resultsLocator = $(this).attr('id');
				console.log(resultsLocator);
				//Ajax call for more results according to locator
				$.ajax({
					type:"POST",
					url:'searchengine.php',
					data:{search : query, unq : unqIdentifier, locator : resultsLocator},
					success:function(data){
						console.log(data);
						results = JSON.parse(data);

						var html = '<div class="row-md-4">';
						for (resultsLocator = 0; resultsLocator<results.json.length || ((resultsLocator%10==0 && resultsLocator!=0) && resultsLocator>0); resultsLocator++){
							html+='<div><h1><a href = "' + results.json[resultsLocator].href + '" target=_blank>' + results.json[resultsLocator].fileName + '</a></h1>';
				    		html+="<h6>By " + results.json[resultsLocator].author + "</h6>";
				    		html+='<h4>' + results.json[resultsLocator].preview +'</h4></div></div>';
						}
						$('#results').html(html);
					}
				});
				$('main').html(html);
				$('li').find('#'+counter).attr('font-weight','bold');
			});
		}
	});
});