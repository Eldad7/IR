var details = [];
var index;
var fileItems = [];
$(document).ready(function(){
	var vars = [], hash;
    var hashes = window.location.href.slice(window.location.href.indexOf('?') + 1).split('&');
    for(var i = 0; i < hashes.length; i++)
    {
        hash = hashes[i].split('=');
        vars.push(hash[0]);
        vars[hash[0]] = hash[1];
    }
   	if ('alert' in vars)
   		alert(vars['alert']);
	$('#allFiles').on('click', function(){
		$("#results").html('');
		$.getJSON( "admingetfiles.php", function( data ) {
			$.each( data, function( key, val ) {
			  var item='<div class="files" id='+key+'>';
			  item+='<div class="checkbox" id="'+key+'"><input type="checkbox"';
			  if (data[key].hidden == 0)
			  	item+='checked';
			  item+='></div>';
			  item+='<p onchange=>Name: ' + data[key].name + '</p>';
			  item+='<p>Author: ' + data[key].author + '</p>';
			  fileItems.push(item);
			});
		var html = '<div class="row-md-4">';
		for (var i=0; i<fileItems.length || (i%4==0 && i!=0); i++){
			html+=fileItems[i];
		}
		html+='<ul>';
		var counter = 1;
		for (var i=0; i<fileItems.length; counter++){
			html+='<li id="'+i+'">'+counter+'</li>';
			i+=4;
		}
		html+='</ul></div>';
		$('main').html(html);
		$('li').find('#0').attr('font-weight','bold');
		$('li').on('click', function(){
			var currentCounter = $(this).attr('id');
			var html = '<div class="row-md-4">';
			for (i=currentCounter;i<fileItems.length;i++){
				html+=fileItems[i];
			}
			var counter = 1;
			for (var i=0; i<fileItems.length; counter++){
				html+='<li id="'+i+'">'+counter+'</li>';
				i+=4;
			}
			console.log(html);
			$('main').html(html);
			$('li').find('#'+currentCounter).attr('font-weight','bold');
		})
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
		  		details.push('<form action="parser.php", method="POST"><div class="col"><div class="row-md-4"><label for="input">File:</label><input type="text" name="filename" value="'+val+'" readonly></div><div class="row-md-4"><label for="input">Author:</label><input type="text" name="author" value=""></div></div><input type="submit"></input></form>');
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

function openFile(href,name){
	$.ajax({
		type:"POST",
		url:'../getfile.php',
		data:{href},
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