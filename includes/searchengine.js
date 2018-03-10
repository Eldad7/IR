$(document).ready(function(){
	$('#or').on('click', function(){
		$('#search').val($('#search').val()+' | ');
	});
	$('#and').on('click', function(){
		$('#search').val($('#search').val()+' + ');
	});
	$('#not').on('click', function(){
		$('#search').val($('#search').val()+' - ');
	});
	$.getJSON( "searchengine.php", function( data ) {
		$.each( data, function( key, val ) {
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
});