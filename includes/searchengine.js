var query = null, queryString = [], unqIdentifier = null, resultsLocator=0, counter=1, words = [], results = [];

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
        words = query.split(" ");
    $( "#search" ).val(query);
	
	$.ajax({
		type:"POST",
		url:'searchengine.php',
		data:{search:query},
		success:function(data){
			results = JSON.parse(data);
			console.log(results);
			unqIdentifier = results.unq;
			var html = '<center>';
			if (results.closest!=query)
				html+='<h3>Showing results for ' + results.closest + '</h3>';
			html+='<div id="results" style="width:400px"><div class="row-md-4">';
			if (results.totalResults>0){
				for (counter = resultsLocator; counter<results.json.length || (counter%10==0 && counter!=0); counter++){
					html+='<button id="btn'+counter+'"><h2>' + results.json[counter].fileName + '</2></button>';
		    		html+="<h6>By " + results.json[counter].author + "</h6>";
		    		html+='<h4>' + results.json[counter].preview +'</h4></div>';
				}
				html+='</div><ul>';
				for (var i=0, counter=1; i<results.totalResults && i<110; i++, counter++){
					html+='<li id="'+(i*10)+'">'+counter+'</li>';
					i+=10;
				}
				html+='</ul>';
			}
			else{
				html+='<h1>No results found. Try another search</h1>';
			}
			html+='</div></center>';
			$('main').html(html);
			for (var counter = resultsLocator; counter<resultsLocator+10 && counter<results.json.length; counter++){
				$('#btn'+counter).on('click',function(){
					var id = $(this).attr('id');
					id = id.replace('btn','');
					openFile(results.json[id].href, results.json[id].fileName);});
			}
			$('li').find('#0').attr('font-weight','bold');
			$('li').bind('click', function(){
				resultsLocator = $(this).attr('id');
				//Ajax call for more results according to locator
				$.ajax({
					type:"POST",
					url:'searchengine.php',
					data:{search : query, unq : unqIdentifier, locator : resultsLocator},
					success:function(data){
						console.log(data);
						results = JSON.parse(data);

						var html = '<div class="row-md-4">';
						for (counter = resultsLocator; counter<results.json.length || (counter%10==0 && counter!=0); counter++){
							html+='<button id="btn'+counter+'"><h2>' + results.json[counter].fileName + '</2></button>';
				    		html+="<h6>By " + results.json[counter].author + "</h6>";
				    		html+='<h4>' + results.json[counter].preview +'</h4></div>';
						}
						$('#results').html(html);
						for (var counter = resultsLocator; counter<resultsLocator+10 && counter<results.json.length; counter++){
							$('#btn'+counter).on('click',function(){
								var id = $(this).attr('id');
								id = id.replace('btn','');
								openFile(results.json[id].href, results.json[id].fileName);});
						}
					}
				});
				$('main').html(html);
				$('li').find('#'+counter).attr('font-weight','bold');
			});
		}
	});
	console.log('done');
	$('#or').on('click', function(){
		$('#search').val($('#search').val()+' | ');
	});
	$('#and').on('click', function(){
		$('#search').val($('#search').val()+' + ');
	});
	$('#not').on('click', function(){
		$('#search').val($('#search').val()+' - ');
	});
});

function openFile(href,name){
	$.ajax({
		type:"POST",
		url:'getfile.php',
		data:{href,values:words},
		success:function(data){	
			updateModal(name,data,true);
		}
	});
}

function updateModal(title,body,footer=false,redirect=false){
    if (title!=null)
        $('.modal-title').text(title);
    if (body!=null)
        $('.modal-body').html(body);
    if (footer)
        $(".modal-content").append('<div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal">Close</button></div>');
    if (!$('#myModal').hasClass('in'))
        $('#myModal').modal('show');

    $('#myModal').on('hidden.bs.modal', function (e) {
        $('#myModal').modal('hide');
        $(".modal-footer").remove();
    });
}