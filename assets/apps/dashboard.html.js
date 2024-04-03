var dashboard = {
	loadPage: function(){		
		$.ajax({
			method: "POST",
			url: api_url("pages/dashboard"),
			data: {
				"username": localStorage.getItem("username"),
				"password": localStorage.getItem("password"),
				"ukey": localStorage.getItem("ukey")
			},
			dataType: "json"
		}).done(function(res){
			if(res.status == "success"){
				$("#total-appointment").text(res.data.appointments);
				$("#total-spent").text(res.data.total);
				$("#dtc").text(res.data.daysToCheck);
				$("#total-record").text(res.data.records);
				$("#name").text(res.data.name);
				
				if(res.data.upcoming.length > 0){
					$("#upcoming").html("");
					
					res.data.upcoming.forEach(function(x){
						$("#upcoming").append('\
							<div class="card mb-3">\
								<div class="card-body">\
									<strong><u>'+ x.clinic.name +'</u></strong> @ '+ x.date +' '+ x.time +'<br />\
									\
									<p>'+ x.reason +'</p>\
								</div>\
							</div>\
						');
					});
				}
				
			}else{
				alert(res.message);
			}
		});
	}
}