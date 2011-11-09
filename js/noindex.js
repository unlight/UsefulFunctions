$(function(){
	if (typeof(jQuery.base64Decode) != "undefined") {
		jQuery(function($){
			$("input.NoIndex").each(function(Index, Element) {
				$(Element).replaceWith( $.base64Decode(Element.value) );
			});
		});
	}
});