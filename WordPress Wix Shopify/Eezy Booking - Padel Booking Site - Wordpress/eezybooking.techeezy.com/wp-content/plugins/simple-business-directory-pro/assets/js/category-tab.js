
var lat = parseFloat(sld_variables.latitute);
var lon = parseFloat(sld_variables.longitute);

if( ! localStorage.getItem("qcld_map_latitite") ){
	localStorage.setItem("qcld_map_latitite",  lat );
}

if( ! localStorage.getItem("qcld_map_longitute") ){
	localStorage.setItem("qcld_map_longitute", lon );
}

if( sld_variables.current_location_map == 'on' ){

	if( typeof google !== 'undefined' ){

		if (navigator.geolocation) {
			navigator.geolocation.getCurrentPosition(showMapPositions);
			
		}

		function showMapPositions( position ) {
           var latVal  = position.coords.latitude;
           var lonVal = position.coords.longitude;

           	if( latVal != '' && lonVal != '' && ! localStorage.getItem("qcld_map_current_position") ){

           		localStorage.setItem("qcld_map_latitite",  latVal );
           		localStorage.setItem("qcld_map_longitute", lonVal );
           		localStorage.setItem("qcld_map_current_position", 1 );
           		location.reload();

           	}

        }
    }

}



jQuery(document).ready(function($){

	var disableAutoPan = sld_variables.pd_enable_map_autopan;
	if( disableAutoPan != 'off' ){
		disableAutoPan = false;
	}else{
		disableAutoPan = true;
	}
		
	
	jQuery('.qcld_pd_tablinks').on('click', function(evt){
		var qcld_pd_event = jQuery(this).data('contentid');
		var i, qcld_pd_tabcontent, qcld_pd_tablinks;
		qcld_pd_tabcontent = document.getElementsByClassName("qcld_pd_tabcontent");
		for (i = 0; i < qcld_pd_tabcontent.length; i++) {
			qcld_pd_tabcontent[i].style.display = "none";
		}
		qcld_pd_tablinks = document.getElementsByClassName("qcld_pd_tablinks");
		for (i = 0; i < qcld_pd_tablinks.length; i++) {
			qcld_pd_tablinks[i].className = qcld_pd_tablinks[i].className.replace(" qcld_pd_active", "");
		}
		document.getElementById(qcld_pd_event).style.display = "block";
		evt.currentTarget.className += " qcld_pd_active";

		
		jQuery('#'+qcld_pd_event +' .qcpd-single-list-pd').each(function(e){
			
			if(jQuery(this).find('.pdp-holder').length > 0 && jQuery(this).find('.pdp-holder > .jp-current').length==0){

				var containerId = jQuery(this).find('.pdp-holder').attr('id');
				var containerList = jQuery(this).find('ul').attr('id');
				console.log(containerList);
				jQuery("#"+jQuery(this).find('.pdp-holder').attr('id')).jPages({
					containerID : containerList,
					perPage : per_page,
				});
				
			}
			
		})
		
		
		
		jQuery('.sbd_main_wrapper:visible .qc-grid').packery({
			itemSelector: '.qc-grid-item',
			gutter: 10
		});
		jQuery( '.sbd_main_wrapper:visible').siblings('.sbd-filter-area').find('.filter-btn[data-filter="all"]' ).trigger( "click" );
		
		var newmapid = jQuery('#'+qcld_pd_event+' .sbd_map').attr('id');
		
		if( typeof google !== 'undefined' ){
			if(typeof(newmapid)!=="undefined" || newmapid!==''){
				initializetab(newmapid);
			}
		}
		
	});
	
	
	if( typeof google !== 'undefined' ){
	
		var geocoder;
		var map;
		var circle = null;
		var markers = [];
		var iw;
		
		var mapid = "sbd_all_location0";
		if(jQuery('#'+mapid).length>0){
			//google.maps.event.addDomListener(window, "load", initializetab(mapid));
			//window.addEventListener( "load", initializetab( mapid ) );

	        window.onload = function () {
	            initializetab( mapid )
	        };


		}
		var oms;
		
	}

	var lat = parseFloat(sld_variables.latitute);
	var lon = parseFloat(sld_variables.longitute);

	if( ! localStorage.getItem("qcld_map_latitite") ){
		localStorage.setItem("qcld_map_latitite",  lat );
	}

	if( ! localStorage.getItem("qcld_map_longitute") ){
		localStorage.setItem("qcld_map_longitute", lon );
	}

	if( sld_variables.current_location_map == 'on' ){

		if( typeof google !== 'undefined' ){

			if (navigator.geolocation) {
				navigator.geolocation.getCurrentPosition(showMapTabPositions);
				
			}

			function showMapTabPositions( position ) {
	           var latVal  = position.coords.latitude;
	           var lonVal = position.coords.longitude;

	           	if( latVal != '' && lonVal != '' && ! localStorage.getItem("qcld_map_current_position") ){

	           		localStorage.setItem("qcld_map_latitite",  latVal );
	           		localStorage.setItem("qcld_map_longitute", lonVal );
	           		localStorage.setItem("qcld_map_current_position", 1 );
	           		location.reload();

	           	}

	        }
	    }

	}


	function initializetab(mapid) {

		if( typeof google !== 'undefined' ){

			map = new google.maps.Map(
			document.getElementById(mapid), {
			  center: { lat: parseFloat(localStorage.getItem("qcld_map_latitite")), lng: parseFloat(localStorage.getItem("qcld_map_longitute")) },
			  zoom: parseInt(sld_variables.zoom),
			  mapId: sld_variables.sbd_google_map_id
			});

			// if( (typeof pd_snazzymap_js === "object" || typeof pd_snazzymap_js === 'function') && (pd_snazzymap_js !== null) ){
			// 	map.setOptions({styles: pd_snazzymap_js});
			// }
			// geocoder = new google.maps.Geocoder();
			// oms = new OverlappingMarkerSpiderfier(map, {
			//   markersWontMove: true,
			//   markersWontHide: true,
			//   basicFormatEvents: true
			// });
			myLooptab();

		}
	}



	var i = 0;                     

	function myLooptab () {
		if( typeof google !== 'undefined' ){
			var bounds = new google.maps.LatLngBounds();
			markers = [];
			jQuery(".qcld_pd_tabcontent:visible .qc-grid-item ul li:visible").each(function(){
				var obj = jQuery(this);
				
				var icons = obj.find('.pd-bottom-area').clone();
				
				icons.find('.pd-map').removeClass('open-mpf-sld-link');
			
				if(typeof(obj.attr('data-address'))!=='undefined' && obj.attr('data-address')!=''){
					icons.find('.pd-map').attr("href", "https://www.google.com/maps/dir/?api=1&origin=none&destination="+encodeURIComponent(obj.attr('data-address'))+"&travelmode=driving");
					icons.find('.pd-map').attr("target", "_blank");
				}
			icons.find('.open-mpf-sld-link').remove();
			icons.find('.pd-map').remove();
			icons = icons.html();
			
			var custom_content = obj.find('.sbd_custom_content').clone();
			custom_content = custom_content.html();
			//others information
			var others_info = '';
			if(obj.attr('data-local')!=''){
				others_info +="<p><b>Local: </b>"+obj.attr('data-local')+"</p>";
			}
			if(obj.attr('data-phone')!=''){
				others_info +="<p><b>Phone: </b>"+obj.attr('data-phone')+"</p>";
			}
			if(obj.attr('data-businesshour')!=''){
				others_info +="<p><b>Business Hours: </b>"+obj.attr('data-businesshour')+"</p>";
			}
				
				var imgurl = '';
				if(obj.find('img').length>0){
					imgurl = obj.find('img').attr('src');
				}
				var target = '';
			
				if(typeof(obj.find('a').attr('target'))!=='undefined'){
					target = 'target="_blank"';
				}
				if(obj.attr('data-latlon')!=='' && typeof(obj.attr('data-latlon'))!=='undefined'){
					i++;
					
					var locations = obj.attr('data-latlon');
					var latlng = locations.split(',');
					var title = obj.attr('data-title');
					var address = obj.attr('data-address');
					var url = obj.attr('data-url');
					var subtitle = obj.attr('data-subtitle');
					
					var markericon = '';
					if(sld_variables.global_marker!=''){
						markericon = sld_variables.global_marker;
					}
					if(typeof(obj.attr('data-paid'))!='undefined' && obj.attr('data-paid')!=''){
						markericon = sld_variables.paid_marker_default; //default paid marker
						if(sld_variables.paid_marker!=''){
							markericon = sld_variables.paid_marker; // If paid marker is set then override the default
						}
					}
					
					var map_marker_id = jQuery(this).attr('id');

					if(typeof(obj.attr('data-marker'))!='undefined' && obj.attr('data-marker')!=''){
						markericon = obj.attr('data-marker'); // If icon is set in the item it self. Most priority.
					}
					var icon = markericon;
					if( markericon != '' ){
					
						var beachFlagImg 	= document.createElement("img");
						beachFlagImg.src 	= markericon;
						beachFlagImg.width 	= 30;
						beachFlagImg.height = 30;
						var marker = new google.maps.marker.AdvancedMarkerElement({
							map: map,
							position:  new google.maps.LatLng(latlng[0],latlng[1]),
							title: map_marker_id,
							content: beachFlagImg,
						});
					}else{
						var marker = new google.maps.marker.AdvancedMarkerElement({
							map: map,
							position:  new google.maps.LatLng(latlng[0],latlng[1]),
							title: map_marker_id,
						});

					}
					markers.push(marker);
					infoWindow(marker, map, title, address, url, subtitle, imgurl, target, icons, custom_content, others_info);
					//bounds.extend(marker.getPosition());
					//map.fitBounds(bounds);
					bounds.extend(marker.position);

					if( sld_variables.sbd_map_fitbounds == 'on' ){

						map.fitBounds(bounds);

					}
					
					
				}
				
			});

			if(cluster.cluster){
				//var markerCluster = new MarkerClusterer(map, markers,{imagePath: 'https://developers.google.com/maps/documentation/javascript/examples/markerclusterer/m'});
				var markerCluster = new markerClusterer.MarkerClusterer({ markers, map });
			}
			

			/*for(var i=0;i<all_locations.length;i++){
				markerAddress(all_locations, i);
			}*/
		}
	}
	
	
	function clearmarker(){
		if( typeof google !== 'undefined' ){
			for (var i = 0; i < markers.length; i++) {
			  markers[i].setMap(null);
			}
		}
	}

	 
	jQuery('.qcld_pd_tabcontent .sbd_radius_find').addClass('sbd_tab_radius_find').removeClass('sbd_radius_find');
	jQuery('.qcld_pd_tabcontent .sbd_radius_clear').addClass('sbd_tab_radius_clear').removeClass('sbd_radius_clear');

	jQuery(document).on('click', '.qcld_pd_tabcontent .sbd_tab_radius_find', function(e){


		if( typeof google !== 'undefined' ){
			var address = jQuery('.qcld_pd_tabcontent:visible .sbd_location_name').val();
			var radiusi = jQuery('.qcld_pd_tabcontent:visible .sbd_distance').val();
			markers = [];
			var findmapid = jQuery('.qcld_pd_tabcontent:visible .sbd_map:visible').length>0?jQuery('.qcld_pd_tabcontent:visible .sbd_map:visible').attr('id'):'';
			if( typeof google !== 'undefined' ){
				if(findmapid!=''){
					map = new google.maps.Map(document.getElementById(findmapid), {
						center: { lat: parseFloat(localStorage.getItem("qcld_map_latitite")), lng: parseFloat(localStorage.getItem("qcld_map_longitute")) },
						zoom: parseInt(sld_variables.zoom),
						//mapTypeId: 'roadmap',
						//gestureHandling: 'cooperative',
				  		mapId: sld_variables.sbd_google_map_id
					});

					// oms = new OverlappingMarkerSpiderfier(map, {
					//   markersWontMove: true,
					//   markersWontHide: true,
					//   basicFormatEvents: true
					// });
				}
			}
			var bounds = new google.maps.LatLngBounds();
			if(address==''){
				alert(sld_variables.distance_location_text);
				return;
			}
			
			if(radiusi==''){
				alert(sld_variables.distance_value_text);
				return;
			}
			
			if(jQuery('.qcld_pd_tabcontent:visible .sdb_distance_cal').val()=='miles'){
				radiusi = radiusi*1.60934;
			}
			

			var radius = parseInt(radiusi, 10)*1000;
			
			var geocoder = new google.maps.Geocoder();
			
			geocoder.geocode( { 'address': address}, function(results, status) {
			  if (status == google.maps.GeocoderStatus.OK) {
				  
				var latitude = results[0].geometry.location.lat();
	            var longitude = results[0].geometry.location.lng();
	            var latlng = new google.maps.LatLng(latitude, longitude);
	            if(latlng){
	            	map.setCenter(latlng);
	            }
				//map.setCenter(results[0].geometry.location);
				var searchCenter = results[0].geometry.location;
				
				/*
				var marker = new google.maps.Marker({
					map: map,
					position: results[0].geometry.location
				});
				*/
				if (circle) circle.setMap(null);
				circle = new google.maps.Circle({center:searchCenter,
												radius: radius,
												strokeColor: sld_variables.radius_circle_color,
												strokeOpacity: 0.8,
												strokeWeight: 2,
												fillColor: sld_variables.radius_circle_color,
												fillOpacity: 0.35,
												map: map});

				
				clearmarker();
				markers=[];
				jQuery(".qcld_pd_tabcontent:visible .qc-grid-item ul li").each(function(){
					var obj = jQuery(this);
					
					var icons = obj.find('.pd-bottom-area').clone();
					icons.find('.pd-map').removeClass('open-mpf-sld-link');
				
				if(typeof(obj.attr('data-address'))!=='undefined' && obj.attr('data-address')!=''){
					icons.find('.pd-map').attr("href", "https://www.google.com/maps/dir/?api=1&origin=none&destination="+encodeURIComponent(obj.attr('data-address'))+"&travelmode=driving");
					icons.find('.pd-map').attr("target", "_blank");
				}
				icons.find('.open-mpf-sld-link').remove();
				icons.find('.pd-map').remove();
				icons = icons.html();
				
				var custom_content = obj.find('.sbd_custom_content').clone();
				custom_content = custom_content.html();
				//others information
				var others_info = '';
				if(obj.attr('data-local')!=''){
					others_info +="<p><b>Local: </b>"+obj.attr('data-local')+"</p>";
				}
				if(obj.attr('data-phone')!=''){
					others_info +="<p><b>Phone: </b>"+obj.attr('data-phone')+"</p>";
				}
				if(obj.attr('data-businesshour')!=''){
					others_info +="<p><b>Business Hours: </b>"+obj.attr('data-businesshour')+"</p>";
				}
					
					var imgurl = '';
					if(obj.find('img').length>0){
						imgurl = obj.find('img').attr('src');
					}
					var target = '';
				
					if(typeof(obj.find('a').attr('target'))!=='undefined'){
						target = 'target="_blank"';
					}
					if(obj.attr('data-latlon')!=='' && typeof(obj.attr('data-latlon'))!=='undefined'){
						i++;
						
						var locations = obj.attr('data-latlon');
						var latlng = locations.split(',');
						var title = obj.attr('data-title');
						var address = obj.attr('data-address');
						var url = obj.attr('data-url');
						var subtitle = obj.attr('data-subtitle');
						var markericon = '';
						if(sld_variables.global_marker!=''){
							markericon = sld_variables.global_marker;
						}
						if(typeof(obj.attr('data-paid'))!='undefined' && obj.attr('data-paid')!=''){
							markericon = sld_variables.paid_marker_default; //default paid marker
							if(sld_variables.paid_marker!=''){
								markericon = sld_variables.paid_marker; // If paid marker is set then override the default
							}
						}
						
						var map_marker_id = jQuery(this).attr('id');

						if(typeof(obj.attr('data-marker'))!='undefined' && obj.attr('data-marker')!=''){
							markericon = obj.attr('data-marker'); // If icon is set in the item it self. Most priority.
						}
						
						if (google.maps.geometry.spherical.computeDistanceBetween(new google.maps.LatLng(latlng[0],latlng[1]),searchCenter) < radius) {
							var icon = markericon;
							if( markericon != '' ){
						
								var beachFlagImg 	= document.createElement("img");
								beachFlagImg.src 	= markericon;
								beachFlagImg.width 	= 30;
								beachFlagImg.height = 30;
								var marker = new google.maps.marker.AdvancedMarkerElement({
									map: map,
									position:  new google.maps.LatLng(latlng[0],latlng[1]),
									title: map_marker_id,
									content: beachFlagImg,
								});
							}else{
								var marker = new google.maps.marker.AdvancedMarkerElement({
									map: map,
									position:  new google.maps.LatLng(latlng[0],latlng[1]),
									title: map_marker_id,
								});

							}
							markers.push(marker);
							infoWindow(marker, map, title, address, url, subtitle, imgurl, target, icons, custom_content, others_info);
							bounds.extend(marker.position);
							map.fitBounds(bounds);
							
							obj.removeClass("showMe");
							obj.show();
							obj.addClass("showMe");
							
						}else{
							obj.fadeOut();
							//obj.hide();
							obj.removeClass("showMe");	
						}
						

						
					}
					
				})
				
				map.fitBounds(circle.getBounds());
				var visibleItems = 0;
				var totalvisibleitem = 0;
				jQuery(".qcpd-single-list-pd, .qcpd-single-list-pd-1, .opd-list-pd-style-8, .opd-list-pd-style-9, .pd-container, .qc-sbd-single-item-11, .qc-sld-masonary-15").each(function(){
		            
					visibleItems = jQuery(this).find("li.showMe").length;
					totalvisibleitem += jQuery(this).find("li.showMe").length;
					
					//console.log(visibleItems);
					
					if(visibleItems==0){
						jQuery(this).hide();
						
					}else{
						jQuery(this).show();
					}
				});
				
				
				
				setTimeout(function(){
					jQuery('.qc-grid').packery({
					  itemSelector: '.qc-grid-item',
					  gutter: 10
					});
					setTimeout(function(){
						if(totalvisibleitem==0){
							alert(sld_variables.distance_no_result_text);
						}
					},500)
					
				},1000)
				
				
			  } else {
				//alert('Geocode was not successful for the following reason: ' + status);
			  }
			});
		}
		return;
	})

	jQuery(document).on('click', '.qcld_pd_tabcontent .sbd_tab_radius_clear', function(e){
		if( typeof google !== 'undefined' ){
			var obj = jQuery(this);
			jQuery('.qcld_pd_tabcontent:visible .sbd_location_name').val('');
			jQuery('.qcld_pd_tabcontent:visible .sbd_distance').val('');
			if (circle) circle.setMap(null);
			jQuery(".qcpd-single-list-pd, .qcpd-single-list-pd-1, .opd-list-pd-style-8, .opd-list-pd-style-9, .pd-container").each(function(){
				
				jQuery(this).show();
				
			});

			jQuery('.qcld_pd_tabcontent:visible .pd_search_filter').trigger('keyup');
			jQuery('.qcld_pd_tabcontent:visible .sbd-filter-area a[data-filter="all"]').trigger('click');
			
			jQuery(".qcld_pd_tabcontent:visible .qc-grid-item ul li").each(function(){
				
				jQuery(this).show();
				
			})
			
			
			clearmarker();
			myLooptab();
			setTimeout(function(){
				jQuery('.qc-grid').packery({
				  itemSelector: '.qc-grid-item',
				  gutter: 10
				});
			},1000)
		}
	})

	
	
	function infoWindow(marker, map, title, address, url, subtitle, imgurl, target, icons, custom_content, others_info) {
	    if( typeof google !== 'undefined' ){
		   // oms.forgetAllMarkers();
		    google.maps.event.addListener(marker, 'click', function(){
		    	if (typeof iw !== 'undefined' && iw) {
					iw.close();
				}

				var main_click_check = cluster.main_click;
				
				var html = "<div>";
				
				if(imgurl!='' && !cluster.image_infowindow){
					html += "<div class='sbd_pop_img'><img src='"+imgurl+"' /></div>";
				}
				
		        html += "<div class='sbd_pop_text'><h3>" + title + "</h3><p>" + subtitle + "</p>";
				
				if(address!=''){
					html+="<p><b><i class='fa fa-map-marker fa-map-marker-alt' aria-hidden='true'></i> </b>" + address + "</p>";
				}
				if(others_info!=''){
					html+=others_info;
				}
				if(custom_content!='' && typeof(custom_content)!='undefined'){
					html+=custom_content;
				}
				if( main_click_check == 2 ){
					html +="<a style='text-align: center;display:block;font-weight: bold;' class='popup_instead_marker_details_trigger' item-id='"+marker.mapSelectorID+"' href='#' >"+sld_variables.view_details+"</a>";
				}else if(url!=='' && url.length > 2 && main_click_check !== 2 ){
					html +="<a style='text-align: center;display:block;font-weight: bold;' href='" + url + "' "+ target +">"+sld_variables.view_details+"</a>";
				}
				html +="</div></div>"
				html +="<div class='sbd_bottom_area_marker'>"+icons+"</div>";
				
				html+="</div></div>";
				iw = new google.maps.InfoWindow({
		            content: html,
		            maxWidth: 230,
					disableAutoPan: disableAutoPan
					
		        });
				iw.setZIndex(9999);
		        iw.open(map, marker);
		    });
			//oms.addMarker(marker);
		}
	}



	$(document).on("click", ".popup_instead_marker_details_trigger", function(e){
		e.preventDefault();
		var value = jQuery(this).attr('item-id');
		jQuery('#'+value + ' .popup_instead_marker_details.open-mpf-sld-link').trigger('click');
		

	});


	//code for filter area
	jQuery(document).on("click",".qcld_pd_tabcontent .sbd-filter-area a", function(event){
		event.preventDefault();
		markers = [];
		var sbd_mode='categoryTab';
		if( !jQuery(this).parent('.sbd-filter-area').parent().hasClass('qcld_pd_tabcontent') ){
			sbd_mode='';
			return false;
		}

		var findmapid = jQuery('.qcld_pd_tabcontent:visible .sbd_map:visible').length>0?jQuery('.qcld_pd_tabcontent:visible .sbd_map:visible').attr('id'):'';
		if( typeof google !== 'undefined' ){
			if(findmapid!=''){
				map = new google.maps.Map(document.getElementById(findmapid), {
					center: { lat: parseFloat(localStorage.getItem("qcld_map_latitite")), lng: parseFloat(localStorage.getItem("qcld_map_longitute")) },
					zoom: parseInt(sld_variables.zoom),
					//mapTypeId: 'roadmap',
					//gestureHandling: 'cooperative',
			  		mapId: sld_variables.sbd_google_map_id
				});

				// if( (typeof pd_snazzymap_js === "object" || typeof pd_snazzymap_js === 'function') && (pd_snazzymap_js !== null) ){
				// 	map.setOptions({styles: pd_snazzymap_js});
				// }
				// oms = new OverlappingMarkerSpiderfier(map, {
				//   markersWontMove: true,
				//   markersWontHide: true,
				//   basicFormatEvents: true
				// });
			}
		}
		var bounds = new google.maps.LatLngBounds();
		
		var filterName = jQuery(this).attr("data-filter");

		jQuery(".sbd-filter-area a").removeClass("filter-active");
		jQuery(this).addClass("filter-active");

		if( filterName == "all" )
		{

			jQuery(".qcld_pd_tabcontent:visible #sbdopd-list-holder .qc-grid-item").css("display", "block");
			if(findmapid!=''){
				if( typeof google !== 'undefined' ){
					myLooptab();
				}
			}
		}
		else
		{
			var categoryGridSelector = ".qcld_pd_tabcontent:visible .qc-grid";
			if( jQuery(categoryGridSelector).length == 0 ){
				categoryGridSelector = ".qcld_pd_tabcontent:visible .qcpd-list-hoder";
			}
			jQuery(categoryGridSelector+" .qc-grid-item").css("display", "none");
			jQuery(categoryGridSelector+" .qc-grid-item."+filterName+"").css("display", "block");
			if(findmapid!=''){
				jQuery(categoryGridSelector+" ."+filterName+" ul li").each(function(){
					var obj = jQuery(this);
					
					var icons = obj.find('.pd-bottom-area').clone();
					icons.find('.pd-map').removeClass('open-mpf-sld-link');
					if( typeof google !== 'undefined' ){
						if(typeof(obj.attr('data-address'))!=='undefined' && obj.attr('data-address')!=''){
							icons.find('.pd-map').attr("href", "https://www.google.com/maps/dir/?api=1&origin=none&destination="+encodeURIComponent(obj.attr('data-address'))+"&travelmode=driving");
							icons.find('.pd-map').attr("target", "_blank");
						}
					
						icons.find('.open-mpf-sld-link').remove();
						icons.find('.pd-map').remove();
						icons = icons.html();
						
						var custom_content = obj.find('.sbd_custom_content').clone();
						custom_content = custom_content.html();
						//others information
						var others_info = '';
						if(obj.attr('data-local')!=''){
							others_info +="<p><b>Local: </b>"+obj.attr('data-local')+"</p>";
						}
						if(obj.attr('data-phone')!=''){
							others_info +="<p><b>Phone: </b>"+obj.attr('data-phone')+"</p>";
						}
						if(obj.attr('data-businesshour')!=''){
							others_info +="<p><b>Business Hours: </b>"+obj.attr('data-businesshour')+"</p>";
						}
						
						var imgurl = '';
						if(obj.find('img').length>0){
							imgurl = obj.find('img').attr('src');
						}
						var target = '';
					
						if(typeof(obj.find('a').attr('target'))!=='undefined'){
							target = 'target="_blank"';
						}
						var obj = jQuery(this);
						if(obj.attr('data-latlon')!=='' && typeof(obj.attr('data-latlon'))!=='undefined'){
							var locations = obj.attr('data-latlon');
							var latlng = locations.split(',');
							var title = obj.attr('data-title');
							var address = obj.attr('data-address');
							var url = obj.attr('data-url');
							var subtitle = obj.attr('data-subtitle');
							var markericon = '';
							if(sld_variables.global_marker!=''){
								markericon = sld_variables.global_marker;
							}
							if(typeof(obj.attr('data-paid'))!='undefined' && obj.attr('data-paid')!=''){
								markericon = sld_variables.paid_marker_default; //default paid marker
								if(sld_variables.paid_marker!=''){
									markericon = sld_variables.paid_marker; // If paid marker is set then override the default
								}
							}
					
							var map_marker_id = jQuery(this).attr('id');

							if(typeof(obj.attr('data-marker'))!='undefined' && obj.attr('data-marker')!=''){
								markericon = obj.attr('data-marker'); // If icon is set in the item it self. Most priority.
							}
							var icon = markericon;
							if( markericon != '' ){
					
								var beachFlagImg 	= document.createElement("img");
								beachFlagImg.src 	= markericon;
								beachFlagImg.width 	= 30;
								beachFlagImg.height = 30;
								var marker = new google.maps.marker.AdvancedMarkerElement({
									map: map,
									position:  new google.maps.LatLng(latlng[0],latlng[1]),
									title: map_marker_id,
									content: beachFlagImg,
								});
							}else{
								var marker = new google.maps.marker.AdvancedMarkerElement({
									map: map,
									position:  new google.maps.LatLng(latlng[0],latlng[1]),
									title: map_marker_id,
								});

							}
							markers.push(marker);
							infoWindow(marker, map, title, address, url, subtitle, imgurl, target, icons, custom_content, others_info);
							bounds.extend(marker.position);
							map.fitBounds(bounds);
							
						}
					}
					
				})
			}
		}

		jQuery('.qc-grid').packery({
		  itemSelector: '.qc-grid-item',
		  gutter: 10
		});

	});
	
	

		
	jQuery('.qc-grid').packery({
      itemSelector: '.qc-grid-item',
      gutter: 10
    });


	jQuery(".qcld_pd_tabcontent .pd_search_filter").keyup(function(){

		// Retrieve the input field text and reset the count to zero
		var filter = jQuery(this).val(), count = 0;
		
		var findmapid = jQuery('.qcld_pd_tabcontent:visible .sbd_map').length>0?jQuery('.qcld_pd_tabcontent:visible .sbd_map').attr('id'):'';
		
		if(findmapid!=''){
			if( typeof google !== 'undefined' ){
				//google map code
				map = new google.maps.Map(
				document.getElementById(findmapid), {
					center: { lat: parseFloat(localStorage.getItem("qcld_map_latitite")), lng: parseFloat(localStorage.getItem("qcld_map_longitute")) },
					zoom: parseInt(sld_variables.zoom),
					//mapTypeId: 'roadmap',
					//gestureHandling: 'cooperative',
			  		mapId: sld_variables.sbd_google_map_id
				});

				// if( (typeof pd_snazzymap_js === "object" || typeof pd_snazzymap_js === 'function') && (pd_snazzymap_js !== null) ){
				// 	map.setOptions({styles: pd_snazzymap_js});
				// }
				// oms = new OverlappingMarkerSpiderfier(map, {
				//   markersWontMove: true,
				//   markersWontHide: true,
				//   basicFormatEvents: true
				// });
			}
		var bounds = new google.maps.LatLngBounds();
		// Loop through the comment list
		jQuery(".qcld_pd_tabcontent:visible .qc-grid ul li").each(function(){

			var dataTitleTxt = jQuery(this).children('a').attr('data-title');
			var dataurl = jQuery(this).find('a').attr('href');
			//console.log(dataurl);


			if( typeof(dataurl) == 'undefined' ){
				dataurl = "-----";
			}


			if( typeof(dataTitleTxt) == 'undefined' ){
				dataTitleTxt = "-----";
			}

			var parentH3 = jQuery(this).parentsUntil('.qc-grid-item').children('h3').text();
 
			// If the list item does not contain the text phrase fade it out
			if (jQuery(this).text().search(new RegExp(filter, "i")) < 0 && dataurl.search(new RegExp(filter, "i")) < 0 && dataTitleTxt.search(new RegExp(filter, "i")) < 0 && parentH3.search(new RegExp(filter, "i")) < 0 ) {
				jQuery(this).fadeOut();
				jQuery(this).removeClass("showMe");		
 
			// Show the list item if the phrase matches and increase the count by 1
			}
			else {
				jQuery(this).show();
				jQuery(this).addClass("showMe");
				count++;
				if( typeof google !== 'undefined' ){
					if(findmapid!=''){
						var obj = jQuery(this);
						
						var icons = obj.find('.pd-bottom-area').clone();
						icons.find('.pd-map').removeClass('open-mpf-sld-link');
			
						if(typeof(obj.attr('data-address'))!=='undefined' && obj.attr('data-address')!=''){
							icons.find('.pd-map').attr("href", "https://www.google.com/maps/dir/?api=1&origin=none&destination="+encodeURIComponent(obj.attr('data-address'))+"&travelmode=driving");
							icons.find('.pd-map').attr("target", "_blank");
						}
						icons.find('.open-mpf-sld-link').remove();
						icons.find('.pd-map').remove();
						icons = icons.html();
						
						var custom_content = obj.find('.sbd_custom_content').clone();
						custom_content = custom_content.html();
						//others information
						var others_info = '';
						if(obj.attr('data-local')!=''){
							others_info +="<p><b>Local: </b>"+obj.attr('data-local')+"</p>";
						}
						if(obj.attr('data-phone')!=''){
							others_info +="<p><b>Phone: </b>"+obj.attr('data-phone')+"</p>";
						}
						if(obj.attr('data-businesshour')!=''){
							others_info +="<p><b>Business Hours: </b>"+obj.attr('data-businesshour')+"</p>";
						}
						
						var imgurl = '';
						if(obj.find('img').length>0){
							imgurl = obj.find('img').attr('src');
						}
						var target = '';
			
						if(typeof(obj.find('a').attr('target'))!=='undefined'){
							target = 'target="_blank"';
						}
						if(obj.attr('data-latlon')!=='' && typeof(obj.attr('data-latlon'))!=='undefined'){
							
							var locations = obj.attr('data-latlon');
							var latlng = locations.split(',');
							var title = obj.attr('data-title');
							var address = obj.attr('data-address');
							var url = obj.attr('data-url');
							var subtitle = obj.attr('data-subtitle');
							var markericon = '';
							if(sld_variables.global_marker!=''){
								markericon = sld_variables.global_marker;
							}
							if(typeof(obj.attr('data-paid'))!='undefined' && obj.attr('data-paid')!=''){
								markericon = sld_variables.paid_marker_default; //default paid marker
								if(sld_variables.paid_marker!=''){
									markericon = sld_variables.paid_marker; // If paid marker is set then override the default
								}
							}
					
							var map_marker_id = jQuery(this).attr('id');

							if(typeof(obj.attr('data-marker'))!='undefined' && obj.attr('data-marker')!=''){
								markericon = obj.attr('data-marker'); // If icon is set in the item it self. Most priority.
							}
							var icon = markericon;
							if( markericon != '' ){
					
								var beachFlagImg 	= document.createElement("img");
								beachFlagImg.src 	= markericon;
								beachFlagImg.width 	= 30;
								beachFlagImg.height = 30;
								var marker = new google.maps.marker.AdvancedMarkerElement({
									map: map,
									position:  new google.maps.LatLng(latlng[0],latlng[1]),
									title: map_marker_id,
									content: beachFlagImg,
								});
							}else{
								var marker = new google.maps.marker.AdvancedMarkerElement({
									map: map,
									position:  new google.maps.LatLng(latlng[0],latlng[1]),
									title: map_marker_id,
								});

							}
							markers.push(marker);
							infoWindow(marker, map, title, address, url, subtitle, imgurl, target, icons, custom_content, others_info);
							bounds.extend(marker.position);
							map.fitBounds(bounds);
						}
					}
				}
				
			}
		});
		
		jQuery(".qcpd-single-list-pd, .qcpd-single-list-pd-1, .opd-list-pd-style-8, .opd-list-pd-style-9, .pd-container").each(function(){
			
			var visibleItems = jQuery(this).find("li.showMe").length;
			
			//console.log(visibleItems);
			
			if(visibleItems==0){
				jQuery(this).hide();
			}else{
				jQuery(this).show();
			}
		});

		
		jQuery('.qc-grid').packery({
		  itemSelector: '.qc-grid-item',
		  gutter: 10
		});
		
		}
 
	});


});