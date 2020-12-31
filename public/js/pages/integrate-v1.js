/**
* @author : Untag Si Ganteng
* @company : Unzyp Software
* @circa : 2019
*/
var I = I || {};
I = {
	current: params.current,
	generate:function(){
		$.ajax({
			url:params.url,
			type:"get",
			data: {_token: $('meta[name="csrf-token"]').attr('content')},
			complete:function(xhr){
				if(xhr.status === 200){
					var json = $.parseJSON(xhr.responseText);
					$('input[name="api_key"]').val(json.data);
				} else {
					alert('URL cannot be accessed at this time.');
				}
			}
		});
	},
	init: function(){
		$('#generate_key').click(function(){
			if($('input[name="api_key"]').val() == ""){
				I.generate();
			} else if ($('input[name="api_key"]').val() != "" && $('input[name="api_key"]').val() == I.current) {
				$('#m_modal_1').modal('show');
			} else {
				I.generate();
			}
		});

		$('#cancel-btn').click(function(){
			$('#m_modal_1').modal('hide');
			$('input[name="api_key"]').val(I.current);
		});

		$('.copy_btn').click(function(){
			var $this = $(this),
				$copied = $this.closest('.input-group').siblings('.text-copy'),
				$text = $this.closest('.input-group').find('.to-copy');
			$text.select();
			document.execCommand("copy");
			$copied.fadeIn();
			setTimeout(function(){
				$copied.fadeOut();
			},1500);
		});
	}
};