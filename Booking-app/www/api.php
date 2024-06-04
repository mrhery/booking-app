<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Access-Control-Allow-Origin: *');
date_default_timezone_set('Asia/Kuala_Lumpur');


header("Content-Type: application/json");

$conn = mysqli_connect("127.0.0.1", "root", "", "cloudtunai");
mysqli_query($conn, "SET time_zone = '+08:00'");
//var_dump($conn);


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
						$row = mysqli_fetch_assoc($q);
						die(json_encode([
							"status"	=> "success",
							"message"	=> "Login in success",
							"u_ukey"    => $row['u_ukey']
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

			case "getClinics":
				$userId = getUserID($conn, $_GET['ukey']);

				$query = mysqli_query($conn, "SELECT c.c_name, c.c_ukey FROM clinics as c JOIN clinic_user as cu ON c.c_id = cu.cu_clinic WHERE cu.cu_user = '$userId'");
				$clinics = [];
				while($row = mysqli_fetch_assoc($query)) {
					$clinics[] = $row;
				}
				echo json_encode($clinics);
				exit;
			break;

			case "dashboard":
				$userId = getUserID($conn, $_GET['userkey']);
				$clinicId = getClinicID($conn, $_GET['clinickey']);

				$name = "SELECT u_name FROM users WHERE u_id = $userId";
				$result = mysqli_query($conn, $name);

				$monthAppointments = "SELECT count(*) as total FROM appointments WHERE a_clinic = $clinicId AND a_status = 1 AND YEAR(FROM_UNIXTIME(a_time)) = YEAR(CURRENT_DATE()) AND MONTH(FROM_UNIXTIME(a_time)) = MONTH(CURRENT_DATE())";
                $result1 = mysqli_query($conn, $monthAppointments);
				
                $todayAppointments = "SELECT count(*) as total FROM appointments WHERE a_clinic = $clinicId AND a_status = 1 AND DATE(FROM_UNIXTIME(a_time)) = current_date()";
                $result2 = mysqli_query($conn, $todayAppointments);

				$totalClient = "SELECT count(*) as total FROM customers as c JOIN clinic_customer as cc ON c.c_id = cc.cc_customer WHERE cc.cc_clinic = '$clinicId'";
                $result3 = mysqli_query($conn, $totalClient);

				$reqestAppointments = "SELECT a.a_date, a.a_time, a.a_reason, a.a_createdDate, clinics.c_name as clinic_name, customers.c_name as customer_name
                FROM appointments as a
                LEFT JOIN clinics ON a.a_clinic = clinics.c_id 
                LEFT JOIN customers ON a.a_customer = customers.c_id
                WHERE a.a_status = 0
				AND a.a_clinic = $clinicId
                ORDER BY a.a_createdDate DESC
                LIMIT 3";

                $result4 = mysqli_query($conn, $reqestAppointments);

                $upcomingAppointments = "SELECT a.a_ukey, a.a_date,  a.a_time, a.a_reason, a.a_createdDate, 
				clinics.c_name AS clinicname, customers.c_name AS customername  
                FROM appointments AS a
                LEFT JOIN clinics ON a.a_clinic = clinics.c_id 
                LEFT JOIN customers ON a.a_customer = customers.c_id
                WHERE a.a_status = 1
				AND a.a_clinic = $clinicId
                AND a.a_time >= UNIX_TIMESTAMP(NOW())
                ORDER BY a.a_time ASC
                LIMIT 3";
                $result5 = mysqli_query($conn, $upcomingAppointments);

                $nextAppointments = "SELECT a.a_date,  a.a_time, a.a_reason, a.a_createdDate, 
                clinics.c_name AS clinicname, customers.c_name AS customername, customers.c_phone AS customerphone, customers.c_email AS customeremail, 
                customers.c_ic AS customeric
                FROM appointments as a
                LEFT JOIN clinics ON a.a_clinic = clinics.c_id 
                LEFT JOIN customers ON a.a_customer = customers.c_id
                WHERE a.a_status = 1
				AND a.a_clinic = $clinicId
                AND a.a_time > UNIX_TIMESTAMP(NOW())
                ORDER BY a.a_time ASC
                LIMIT 1";
                $result6 = mysqli_query($conn, $nextAppointments);

				$subQuery = "SELECT a.a_customer
                FROM appointments as a
                LEFT JOIN clinics ON a.a_clinic = clinics.c_id 
                LEFT JOIN customers ON a.a_customer = customers.c_id
                WHERE a.a_status = 1
				AND a.a_clinic = $clinicId
                AND a.a_time >= UNIX_TIMESTAMP(NOW())
                ORDER BY a.a_time ASC
                LIMIT 1";
                $resultSubQuery = mysqli_query($conn, $subQuery);
                $dataSubQuery = mysqli_fetch_assoc($resultSubQuery);

				$data1 = mysqli_fetch_assoc($result1);
				$data2 = mysqli_fetch_assoc($result2);
				$data3 = mysqli_fetch_assoc($result3);
				$data4 = mysqli_fetch_all($result4);
				$data5 = mysqli_fetch_all($result5, MYSQLI_ASSOC);
				$data6 = mysqli_fetch_assoc($result6);

				foreach ($data5 as $key => $appointment) {
					$data5[$key]['a_time'] = date('d M Y h:i A', $appointment['a_time']);
				}

				if($data6){
					$data6['a_time'] = date('d M Y h:i A', $data6['a_time']);
					$customerInfo = parseCustomerIC($data6['customeric']);
					$data6['age'] = $customerInfo['age'];
					$data6['sex'] = $customerInfo['sex'];
				}


				if($dataSubQuery !== null){
					$historyQuery = "SELECT a_reason FROM appointments 
					WHERE a_customer = " . $dataSubQuery['a_customer'] . " 
					AND a_time < UNIX_TIMESTAMP(NOW())
					ORDER BY a_time DESC";
					$result7 = mysqli_query($conn, $historyQuery);

					$lastAppointmentQuery = "SELECT a_time FROM appointments
					WHERE a_customer = " . $dataSubQuery['a_customer'] . " 
					AND a_time < UNIX_TIMESTAMP(NOW())
					ORDER BY a_time DESC
					LIMIT 1";
					$result8 = mysqli_query($conn, $lastAppointmentQuery);

					$data7 = mysqli_fetch_all($result7);
					$data8 = mysqli_fetch_assoc($result8);

					if(empty($data7)){
						$data7 = ['No record'];
					}
					
					if($data8){
						$data8['a_time'] = date('d M Y h:i A', $data8['a_time']);
					} else {
						$data8 = ['a_time' => 'New Patient'];
					}
				
					

					echo json_encode([
						"status" => "success",
						"username" => mysqli_fetch_assoc($result)['u_name'],
						"monthAppointments" => $data1['total'],
						"todayAppointments" => $data2['total'],
						"totalClient" => $data3['total'],
						"reqestAppointments" => $data4,
						"upcomingAppointments" => $data5,
						"nextAppointments" => $data6,
						"nextHistory" => $data7,
						"lastAppointment" => $data8
					]);
				}else{

					$data8 = ['a_time' => 'N/A'];

					echo json_encode([
						"status" => "success",
						"username" => mysqli_fetch_assoc($result)['u_name'],
						"monthAppointments" => $data1['total'],
						"todayAppointments" => $data2['total'],
						"totalClient" => $data3['total'],
						"reqestAppointments" => $data4,
						"upcomingAppointments" => $data5,
						"nextAppointments" => $data6,
						"lastAppointment" => $data8,
	
					]);
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


function parseCustomerIC($customeric) {
    $birthYear = substr($customeric, 0, 2);
    $currentYear = date('Y') % 100;
    $age = ($birthYear <= $currentYear) ? $currentYear - $birthYear : $currentYear + (100 - $birthYear);

    $sexDigit = substr($customeric, -1);
    $sex = $sexDigit % 2 == 0 ? 'Female' : 'Male';

    return ['age' => $age, 'sex' => $sex];
}

function getUserID($conn, $ukey) {
	$userQuery = mysqli_query($conn, "SELECT u_id FROM users WHERE u_ukey = '$ukey'");
	$user = mysqli_fetch_assoc($userQuery);
	return $user['u_id'];
}

function getClinicID($conn, $c_ukey) {
	$clinicQuery = mysqli_query($conn, "SELECT c_id FROM clinics WHERE c_ukey = '$c_ukey'");
	$clinic = mysqli_fetch_assoc($clinicQuery);
	return $clinic['c_id'];
}