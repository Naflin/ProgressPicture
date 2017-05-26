var uploadButton = document.getElementById("upload_widget_opener");
var count = 0;

if (uploadButton !== null) {

uploadButton.addEventListener("click", function() {

    cloudinary.openUploadWidget({ cloud_name: 'ztt', upload_preset: 'oodklpzk', 'folder': ztt_vars.user_name, 'theme': 'minimal' }, 
      function(error, result) {
		
		if(!error) {      	
	     	jQuery(document).ready(function($) {

	     		var zttdata = {
	     			'action': 'ztt_upload_pic',
	     			'ztt_pic_url': result[0].public_id
	     		};

	     		jQuery.post(ztt_vars.ajax_url, zttdata, function(response) {
	     			if(response == "progress") {
	     				$("#ztt-progress-pic").attr("src", result[0].url);
	     			}
	     			else if (response == "base" && count == 0) {
	     				$("#ztt-base-pic").attr("src", result[0].url);
	     				count++;
	     				console.log("Count: " + count);
	     			} else if(response == "progress" && count == 1) {
	     				$("#ztt-progress-pic").attr("src", result[0].url);
	     				$("#ztt-progress-pic").removeAttr("hidden");
	     				count++;
	     			}

	     			if(response == "progress" && count == 0) {
	     				$("#ztt-progress-pic").removeAttr("hidden");
	     			}	     				

	     			if (document.getElementById("ztt-gallery-wrapper") !== null) {

	     				$(".ztt-gallery-error").hide();

	     				$("#ztt-gallery-wrapper").append(
	     					"<span id='" + result[0].public_id + "' class='ztt-show-image'>" +
							"<a href='" + result[0].url + "' data-lightbox='" + result[0].public_id + "'>" +
							"<img class='ztt-gallery' src='" + result[0].url + "'>" +
							"</a>" + 
							"</span>"
	     				);
	     			}

	  				location.reload();

	     			$("#upload_widget_opener").after("<span id='ztt-picture-uploaded-success'>Your picture has been uploaded!</span>").delay(5000).queue(function(next){
		      			$("#ztt-picture-uploaded-success").fadeOut('fast').remove();
		      		});
	     		});
	      	});
      	}

      });//end callback

  }, false);//end event listener

}




jQuery(document).ready(function($) {

	$(".ztt-delete-image").click(function(){
		// var pictureId = this.parentNode.id;
		var src = $('.lb-image').attr('src');
		var slashIndex = 0;
		var dotIndex = 0;
		var string = "";


		for (var i = 0; i <= src.length; i++) {
			if(src[i] == '/')
				slashIndex = i;
			if(src[i] == '.')
				dotIndex = i;
		}

		string = src.substring(slashIndex+1);
		string = string.substring(0, string.length - 4);

		var zttdata = {
 			'action': 'ztt_delete_pic',
 			'ztt_delete_url': string
 		};

 		$("<div>Are you sure you want to delete this image?<div><button class='ztt-delete-image-verify ztt-yellow-button'>Yes</button><button class='ztt-delete-image-deny ztt-yellow-button'>No!</button>").insertAfter(".ztt-delete-image");
 		$(".ztt-delete-image").hide();

 		$(".ztt-delete-image-verify").click(function(){
 			jQuery.post(ztt_vars.ajax_url, zttdata, function(response) {
	 			location.reload(); 
	 			$(".ztt-delete-image").show();
 			});
 		});

 		$(".ztt-delete-image-deny").click(function(){
 			location.reload();
 			$(".ztt-delete-image").show();
 		});

 		
	});

	$(".ztt-change-date").click(function(){		
		var day = dayfield.options[dayfield.selectedIndex].text;
		var month = monthfield.options[monthfield.selectedIndex].text;
		var year = yearfield.options[yearfield.selectedIndex].text;
		var monthNum;

		for(var key in monthtext)
			if(monthtext[key] == month)
				monthNum = key;

			var src = $('.lb-image').attr('src');
			var slashIndex = 0;
			var dotIndex = 0;
			var string = "";


			for (var i = 0; i <= src.length; i++) {
				if(src[i] == '/')
					slashIndex = i;
				if(src[i] == '.')
					dotIndex = i;
			}

			string = src.substring(slashIndex+1);
			string = string.substring(0, string.length - 4);

		var date = {day: day, month: monthNum, year: year, string:string};

		var zttdata = {
			'action' : 'ztt_change_date',
			'ztt_date' : date
		}

		console.log("sent: " + day + "-" + month + "-" + year);

		jQuery.post(ztt_vars.ajax_url, zttdata, function(response) {
			console.log(response);
			location.reload();
     	});

	});

});
