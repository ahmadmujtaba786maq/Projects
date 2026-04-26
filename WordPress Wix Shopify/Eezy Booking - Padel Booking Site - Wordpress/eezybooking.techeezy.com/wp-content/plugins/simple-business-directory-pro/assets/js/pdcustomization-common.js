jQuery(document).ready(function($){
	
	    $('#pd-upload-btn').click(function(e) {
        e.preventDefault();
        var image = wp.media({ 
            title: 'Upload Image',
            multiple: false
        }).open()
        .on('select', function(e){
            
            var uploaded_image = image.state().get('selection').first();
            var image_url = uploaded_image.toJSON().url;
            $('#pd_pf_image_url').val(image_url);
			var html = ['<span class="pd_remove_bg_image">X</span>',
				'<img src="'+image_url+'" alt="" />'
			].join("");
			$('#pd_preview_img').html(html);
        });
    });
	
	$(document).on( 'click', '.pd_remove_bg_image', function(){
		
		$('#pd_preview_img').html('');
		$('#pd_pf_image_url').val('');
		if($('#sbd_prev_image').length>0){
			$('#sbd_prev_image').val('');
		}
	})
	
	
	
	$('#qc_pd_category').on('change', function(){
		var city = $('#qc_pd_category').val();
		
			$.post(
				ajaxurl,
				{
					action : 'qcld_pd_category_filter',
					cat : city,
				},
				function(data){
					
					$('#qc_pd_list').html(data);
					
				}
			);
		
	})

	$('#sld_claim_list').on('change',function(e){
		e.preventDefault();
		if($(this).val()!==''){
			$.post(
				ajaxurl,
				{
					action : 'qcld_sbd_show_list_item',
					listid: $(this).val()
				},
				function(data){
					$('#sld_list_item').html(data);
				}
			);
		}else{
			$('#sld_list_item').html('');
		}
		
	})
	
	$(document).on('click', "#sbd_generate", function(e){
		var objc = $(this);
		
		if($('#sbd_item_link').val()!=''){
			var html = "<div id='sbd_ajax_preloader'><div class='sbd_ajax_loader'></div></div>";
			$('body').append(html);
			$.post(ajaxurl, {
			action: 'qcpd_generate_text', 
			url: $('#sbd_item_link').val(),
			},
			function(data) {
				
				$('#sbd_ajax_preloader').remove();
				var data = JSON.parse(data);
				$('#sbd_title').val(data.title)
				$('#sbd_subtitle').val(data.description)
				
			});
			
		}else{
			alert('Item link field cannot left empty!');
		}

		
	})



	
})

function initMap() {

}
