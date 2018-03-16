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
});

function openFile(href,name){
	$.ajax({
		type:"POST",
		url:'getfile.php',
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