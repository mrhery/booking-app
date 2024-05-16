<?php
header('Access-Control-Allow-Origin: *');

header("Content-Type: application/json");

$conn = mysqli_connect("127.0.0.1", "root", "", "cloudtunai");

if(!$conn){
	die(json_encode([
		"status"	=> "error",
		"message"	=> "Fail connect to database."
	]));
}


if(isset($_GET["key"]) && $_GET["key"] == "asdljnalsdasd"){
	if(isset($_GET["action"])){
		switch($_GET["action"]){
			case "login":
				if(isset($_POST["username"], $_POST["password"])){
					$salt = '5a7347f6fda4a346760af782d2ec126f7b9873ea9a*&(*9yad09707d0a7d0ad!@#!@#!#!#!$#!$!$!#$!$!$!$!$!%@$%#&&*^(7f2bb1fee9abdfd5f4dfc9';
					$string = $_POST["password"];
					 
					$pass = hash("sha256", $string);
					
					$q = mysqli_query($conn, "SELECT * FROM users 
						WHERE u_email = '{$_POST["username"]}' AND
						u_password = '$string'
					");
					
					$n = mysqli_num_rows($q);
					
					if($n > 0){
						die(json_encode([
							"status"	=> "success",
							"message"	=> "Login in success"
						]));
					}else{
						die(json_encode([
							"status"	=> "error",
							"message"	=> "Username or password is incorrect"
						]));
					}
				}else{
					die(json_encode([
						"status"	=> "error",
						"message"	=> "Insufficient request paramater"
					]));
				}
			break;
			case "getAppointments":
				$type = $_GET['type'];
				$dateCondition = "";
			
				switch ($type) {
					case 'today':
						$dateCondition = "DATE(FROM_UNIXTIME(appointments.a_time)) = current_date()";
						break;
					case 'tomorrow':
						$dateCondition = "DATE(FROM_UNIXTIME(appointments.a_time)) = DATE_ADD(current_date(), INTERVAL 1 DAY)";
						break;
					case 'week':
						$dateCondition = "WEEK(FROM_UNIXTIME(appointments.a_time)) = WEEK(current_date())";
						break;
					case 'all':
						$dateCondition = "1"; // Always true, selects all appointments
						break;
				}
			
				$query = mysqli_query($conn, "SELECT appointments.*, clinics.c_name AS clinic_name, customers.c_name AS customer_name FROM appointments 
											   INNER JOIN clinics ON appointments.a_clinic = clinics.c_id
											   INNER JOIN customers ON appointments.a_customer = customers.c_id
											   WHERE $dateCondition
											   ORDER BY appointments.a_time DESC");
				$appointments = [];
				while($row = mysqli_fetch_assoc($query)) {
					$appointments[] = $row;
				}
				echo json_encode($appointments);
				exit;
			break;
			// case "getAppointments":
			// 	$query = mysqli_query($conn, "SELECT appointments.*, clinics.c_name AS clinic_name, customers.c_name AS customer_name FROM appointments 
			// 								   INNER JOIN clinics ON appointments.a_clinic = clinics.c_id
			// 								   INNER JOIN customers ON appointments.a_customer = customers.c_id
			// 								   WHERE DATE(FROM_UNIXTIME(appointments.a_time)) = current_date()
			// 								   ORDER BY appointments.a_time DESC");
			// 	$appointments = [];
			// 	while($row = mysqli_fetch_assoc($query)) {
			// 		$appointments[] = $row;
			// 	}
			// 	echo json_encode($appointments);
			// 	exit;
			// break;
		}
	}else{
		die(json_encode([
			"status"	=> "error",
			"message"	=> "Unknown API endpoint."
		]));
	}

}else{
	die(json_encode([
		"status"	=> "error",
		"message"	=> "API Key is invalid."
	]));
}