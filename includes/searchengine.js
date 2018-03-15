var query = null, queryString = [], unqIdentifier = null, resultsLocator=0;

$(document).ready(function(){
	//Get search parameter and sent to server
	location.search
        .substr(1)
        .split("&")
        .forEach(function (item) {
          queryString = item.split("=");
          if (queryString[0] === 'search')
          	query = decodeURIComponent(queryString[1]);
        });


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
			results = JSON.parse(data);
			unq = results.unq;
			var html = '<div id="results">';
			if (results.search!=query)
				html+='<h3>Showing results for' + results.search + '</h3>';
			html+='<div class="row-md-4">';
			for (resultsLocator = 0; i<results.json.length || (i%10==0 && i!=0); i++){
				html+='<div><h1><a href = "' + results.json[i].href + '" target=_blank>' + results.json[i].name + '</a></h1>';
	    		html+="<h6>By " + results.json[i].author + "</h6>";
	    		html+='<h4>' + results.json[i].preview +'</h4></div></div>';
			}
			html+='<ul>';
			var counter = 1;
			for (var i=0; i<results.totalResults; counter++){
				html+='<li id="'+i+'">'+counter+'</li>';
				i+=10;
			}
			html+='</ul></div>';
			$('main').html(html);
			$('li').find('#0').attr('font-weight','bold');
			$('li').on('click', function(){
				//Ajax call for more results according to locator
				$.ajax({
					type:"POST",
					url:'searchengine.php',
					data:{search : query, unq : unqIdentifier, locator : resultsLocator},
					success:function(data){
						results = JSON.parse(data);
						var html = '<div class="row-md-4">';
						for (resultsLocator = 0; i<results.json.length || (i%10==0 && i!=0); i++){
							html+='<div><h1><a href = "' + results.json[i].href + '" target=_blank>' + results.json[i].name + '</a></h1>';
				    		html+="<h6>By " + results.json[i].author + "</h6>";
				    		html+='<h4>' + results.json[i].preview +'</h4></div></div>';
						}
						$('#results').html(html);
					}
				});
				$('main').html(html);
				$('li').find('#'+currentCounter).attr('font-weight','bold');
			});
		}
	});
});