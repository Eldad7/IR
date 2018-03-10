var details = [];
$(document).ready(function(){
	$('#allFiles').on('click', function(){
		$("#results").html('');
		$.getJSON( "admingetfiles.php", function( data ) {
			console.log(data);
			var html = '<div class="row-md-4">';
			var items = [];
			$.each( data, function( key, val ) {
			  html+='<div class="files" id='+key+'>';
			  html+='<div class="checkbox" id="'+key+'"><input type="checkbox"';
			  if (data[key].hidden == 0)
			  	html+='checked';
			  html+='></div>';
			  html+='<p onchange=>Name: ' + data[key].name + '</p>';
			  html+='<p>Author: ' + data[key].author + '</p>';
			});
		 
		  html+='</div>';
		  $('main').html(html);
		  $('.checkbox').on('change',function(){
		  	$.ajax({
		  		url:'changevisibility.php',
		  		type:'POST',
		  		//need to add file index as post data
		  		data:{'key' : $(this).attr('id')},
		  		success:function(data){
		  			console.log(data);
		  		}
		  	});
		  });
		});
	});
	$('#parseNew').on('click', function(){
		$('main').html('');
		$.getJSON( "admingetfilelist.php", function( data ) {
		  var form = '<center><form action="parser.php", method="POST">';
		  var list = [];
		  var counter = 0;
		  $.each( data, function( key, val ) {
		  	if (val!= '.' && val!='..'){
		  		val=$.trim(val.replace('.txt',''));
		  		details.push('<form action="parser.php", method="POST"><div class="col"><div class="row-md-4"><label for="input">File:</label><input type="text" name="filename" value="'+val+'"></div><div class="row-md-4"><label for="input">Author:</label><input type="text" name="author" value=""></div></div><input type="submit"></input></form>');
		  		list.push('<option id="'+key+'" value="'+val+'">'+val+'</option>');
		  	}
		  });
		  $("<select/>", {
		  		"class": "file-list",
		  		html: list.join(""),
		  		change: function(){
		  			$("#results").html(details[$('select option:selected').attr('id')]);
		  		}
		  	}).appendTo("main");
		  $('#results').html(details[0]);
		});
	});
});