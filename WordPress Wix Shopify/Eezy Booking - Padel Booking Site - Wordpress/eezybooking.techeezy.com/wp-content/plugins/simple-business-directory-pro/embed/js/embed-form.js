function copyToClipboard(elem) {
    // create hidden text element, if it doesn't already exist
    var targetId = "_hiddenCopyText_";
    var isInput = elem.tagName === "INPUT" || elem.tagName === "TEXTAREA";
    var origSelectionStart, origSelectionEnd;
    if (isInput) {
        // can just use the original source element for the selection and copy
        target = elem;
        origSelectionStart = elem.selectionStart;
        origSelectionEnd = elem.selectionEnd;
    } else {
        // must use a temporary form element for the selection and copy
        target = document.getElementById(targetId);
        if (!target) {
            var target = document.createElement("textarea");
            target.style.position = "absolute";
            target.style.left = "-9999px";
            target.style.top = "0";
            target.id = targetId;
            document.body.appendChild(target);
        }
        target.textContent = elem.textContent;
    }
    // select the content
    var currentFocus = document.activeElement;
    target.focus();
    target.setSelectionRange(0, target.value.length);

    // copy the selection
    var succeed;
    try {
        succeed = document.execCommand("copy");
    } catch (e) {
        succeed = false;
    }
    // restore original focus
    if (currentFocus && typeof currentFocus.focus === "function") {
        currentFocus.focus();
    }

    if (isInput) {
        // restore prior selection
        elem.setSelectionRange(origSelectionStart, origSelectionEnd);
    } else {
        // clear temporary content
        target.textContent = "";
    }
    return succeed;
}


jQuery(document).ready(function ($) {
    var appendthis = ("<div class='modal-overlay js-modal-close'></div>");


    /**
     *
     * @type {jQuery}
     * Get shortcode data from custom attribute
     */
    var orderby = $(".js-open-modal").data("orderby");
    var ordering = $(".js-open-modal").data("order");
    var url = $(".js-open-modal").data("url");
    var mode = $(".js-open-modal").data("mode");
    var list_id = $(".js-open-modal").data("list-id");
    var column = $(".js-open-modal").data("column");
    var style = $(".js-open-modal").data("style");
    var search = $(".js-open-modal").data("search");
    var category = $(".js-open-modal").data("category");
    var upvote = $(".js-open-modal").data("upvote");
    var credittitle = $(".js-open-modal").data("credittitle");
    var creditlink = $(".js-open-modal").data("creditlink");
    var tooltip = $(".js-open-modal").data("tooltip");
    var map = $(".js-open-modal").data("map");
    var maponly = $(".js-open-modal").data("maponly");

    var enable_tag_filter = $(".js-open-modal").data("enable_tag_filter");
    var distance_search = $(".js-open-modal").data("distance_search");
    var marker_cluster = $(".js-open-modal").data("marker_cluster");
    var image_infowindow = $(".js-open-modal").data("image_infowindow");
    var item_count = $(".js-open-modal").data("item_count");
    var item_details_page = $(".js-open-modal").data("item_details_page");
    var filterorderby = $(".js-open-modal").data("filterorderby");
    var main_click_action = $(".js-open-modal").data("main_click_action");
    var phone_number = $(".js-open-modal").data("phone_number");
    var paginate_items = $(".js-open-modal").data("paginate_items");
    var per_page = $(".js-open-modal").data("per_page");
    var enable_rtl = $(".js-open-modal").data("enable_rtl");
    var enable_left_filter = $(".js-open-modal").data("enable_left_filter");
    var enable_tag_filter_dropdown = $(".js-open-modal").data("enable_tag_filter_dropdown");
    var enable_map_fullwidth = $(".js-open-modal").data("enable_map_fullwidth");
    var enable_map_fullscreen = $(".js-open-modal").data("enable_map_fullscreen");
    var enable_map_toggle_filter = $(".js-open-modal").data("enable_map_toggle_filter");
    var map_position = $(".js-open-modal").data("map_position");
    var list_title_font_size = $(".js-open-modal").data("list_title_font_size");
    var item_orderby = $(".js-open-modal").data("item_orderby");
    var list_title_line_height = $(".js-open-modal").data("list_title_line_height");
    var title_font_size = $(".js-open-modal").data("title_font_size");
    var review = $(".js-open-modal").data("review");
    var subtitle_font_size = $(".js-open-modal").data("subtitle_font_size");
    var title_line_height = $(".js-open-modal").data("title_line_height");
    var subtitle_line_height = $(".js-open-modal").data("subtitle_line_height");
    var pdmapofflightbox = $(".js-open-modal").data("pdmapofflightbox");
    var filter_area = $(".js-open-modal").data("filter_area");
    var topspacing = $(".js-open-modal").data("topspacing");
	


    $('a[data-modal-id]').click(function (e) {

        e.preventDefault();
        $("body").append(appendthis);
        $(".modal-overlay").fadeTo(500, 0.7);
        //$(".js-modalbox").fadeIn(500);
        var modalBox = $(this).attr('data-modal-id');
        $('#' + modalBox).fadeIn($(this).data());
		
		$('body').addClass('modalOn');
		
    });


jQuery(document).on('click', ".js-modal-close, .modal-overlay", function(event){


   // $(".js-modal-close, .modal-overlay").click(function () {
        $(".modal-box, .modal-overlay").fadeOut(500, function () {
            $(".modal-overlay").remove();
        });
		$('body').removeClass('modalOn');
    });

    $(window).resize(function () {
        $(".modal-box").css({
            //top: ($(window).height() - $(".modal-box").outerHeight()) / 2,
            //left: ($(window).width() - $(".modal-box").outerWidth()) / 2
        });
    });

    $(window).resize();


    $("#generate-igcode").on("click", function () {

        var list_id_ext = "&list_id=";

        if( list_id != "" || list_id != null ){
            list_id_ext = '&list_id=' + list_id;
        }

        var i_height = $("#igheight").val();
        var i_width = $("#igwidth").val();
        var selected_value = $(".iframe-main-select option:selected").text();
        var iframe_template = '<iframe src="' + url + '/?order=' + ordering + '&orderby=' + orderby + '&mode=' + mode + list_id_ext + '&column=' + column +'&tooltip='+ tooltip + '&style=' + style + '&search=' + search + '&category=' + category + '&map=' + map + '&showmaponly=' + maponly + '&upvote=' + upvote + '&enable_tag_filter='+enable_tag_filter + '&distance_search='+distance_search + '&marker_cluster='+marker_cluster + '&image_infowindow='+image_infowindow + '&item_count='+item_count + '&item_details_page='+item_details_page + '&filterorderby='+filterorderby + '&main_click_action='+main_click_action + '&phone_number='+phone_number + '&paginate_items='+paginate_items + '&per_page='+per_page + '&enable_rtl='+enable_rtl + '&enable_left_filter='+enable_left_filter + '&enable_tag_filter_dropdown='+enable_tag_filter_dropdown + '&enable_map_fullwidth='+enable_map_fullwidth + '&enable_map_fullscreen='+enable_map_fullscreen + '&enable_map_toggle_filter='+enable_map_toggle_filter + '&map_position='+map_position + '&list_title_font_size='+list_title_font_size + '&item_orderby='+item_orderby + '&list_title_line_height='+list_title_line_height + '&title_font_size='+title_font_size + '&review='+review + '&subtitle_font_size='+subtitle_font_size + '&title_line_height='+title_line_height + '&subtitle_line_height='+subtitle_line_height + '&pdmapofflightbox='+pdmapofflightbox + '&filter_area='+filter_area + '&topspacing='+topspacing+' " '
            +' scrolling="auto" ' + 'height="' + i_height + 'px' + '" width="' + i_width + selected_value + '" frameborder="0"></iframe>';
			
		var ext = '';
		
        if (credittitle.length > 0 || creditlink.length > 0) {
            if (credittitle.length > 0 && creditlink.length > 0) {
                ext += '<span style="float:right; margin:10px;">List Created by <a href="' + creditlink + '" target="_blank">' + credittitle + '</a></span>';
            } else {
                ext += '<span style="float:right; margin:10px;">List Created by <a href="' + creditlink + '" target="_blank">' + creditlink + '</a></span>';
            }

        }
		
        iframe_template += ext;

        $(".igcode_textarea").html(iframe_template);
        copyToClipboard(document.getElementById("igcode_textarea"));
        $("#igcode_textarea").select();

    });


});