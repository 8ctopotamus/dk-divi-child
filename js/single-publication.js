(function($) {
	$(document).ready(function() {
		$('.et_pb_title_featured_container img').load(function(){
			$('.et_pb_post_content').css('margin-top', $(this).height())
		});		
	})
})(jQuery)