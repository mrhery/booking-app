<?php
header('Access-Control-Allow-Origin: *');

header("Content-Type: application/json");

date_default_timezone_set('Asia/Kuala_Lumpur');


$conn = mysqli_connect("127.0.0.1", "root", "", "cloudtunai");
mysqli_query($conn, "SET time_zone = '+08:00'");


if(!$conn){
	die(json_encode([
		"status"	=> "error",
		"message"	=> "Fail connect to database."
	]));
}


if(isset($_GET["key"]) && $_GET["key"] == "asdljnalsdasd"){
	if(isset($_GET["action"])){
		switch($_GET["action"]){
            case "dashboard":
                $monthAppointments = "SELECT count(*) as total FROM appointments WHERE YEAR(FROM_UNIXTIME(a_time)) = YEAR(CURRENT_DATE()) AND MONTH(FROM_UNIXTIME(a_time)) = MONTH(CURRENT_DATE())";
                $result1 = mysqli_query($conn, $monthAppointments);

                $todayAppointments = "SELECT count(*) as total FROM appointments WHERE DATE(FROM_UNIXTIME(a_time)) = current_date()";
                $result2 = mysqli_query($conn, $todayAppointments);

                $totalClient = "SELECT count(*) as total FROM customers";
                $result3 = mysqli_query($conn, $totalClient);

                $reqestAppointments = "SELECT appointments.a_date, appointments.a_time, appointments.a_reason, appointments.a_createdDate, clinics.c_name, customers.c_name  
                FROM appointments 
                LEFT JOIN clinics ON appointments.a_clinic = clinics.c_id 
                LEFT JOIN customers ON appointments.a_customer = customers.c_id
                WHERE appointments.a_status = 0
                ORDER BY appointments.a_createdDate DESC
                LIMIT 3";
                $result4 = mysqli_query($conn, $reqestAppointments);

                $upcomingAppointments = "SELECT appointments.a_date,  appointments.a_time, appointments.a_reason, appointments.a_createdDate, clinics.c_name AS clinicname, customers.c_name AS customername  
                FROM appointments 
                LEFT JOIN clinics ON appointments.a_clinic = clinics.c_id 
                LEFT JOIN customers ON appointments.a_customer = customers.c_id
                WHERE appointments.a_status = 1
                AND appointments.a_time >= UNIX_TIMESTAMP(NOW())
                ORDER BY appointments.a_time ASC
                LIMIT 3";
                $result5 = mysqli_query($conn, $upcomingAppointments);

                $nextAppointments = "SELECT a.a_date,  a.a_time, a.a_reason, a.a_createdDate, 
                clinics.c_name AS clinicname, customers.c_name AS customername, customers.c_phone AS customerphone, customers.c_email AS customeremail, 
                customers.c_ic AS customeric
                FROM appointments as a
                LEFT JOIN clinics ON a.a_clinic = clinics.c_id 
                LEFT JOIN customers ON a.a_customer = customers.c_id
                WHERE a.a_status = 1
                AND a.a_time > UNIX_TIMESTAMP(NOW())
                ORDER BY a.a_time ASC
                LIMIT 1";
                $result6 = mysqli_query($conn, $nextAppointments);

                $subQuery = "SELECT a.a_customer
                FROM appointments as a
                LEFT JOIN clinics ON a.a_clinic = clinics.c_id 
                LEFT JOIN customers ON a.a_customer = customers.c_id
                WHERE a.a_status = 1
                AND a.a_time >= UNIX_TIMESTAMP(NOW())
                ORDER BY a.a_time ASC
                LIMIT 1";
                $resultSubQuery = mysqli_query($conn, $subQuery);
                $dataSubQuery = mysqli_fetch_assoc($resultSubQuery);

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



                if($result1 && $result2 && $result3 && $result4){
                    $data1 = mysqli_fetch_assoc($result1);
                    $data2 = mysqli_fetch_assoc($result2);
                    $data3 = mysqli_fetch_assoc($result3);
                    $data4 = mysqli_fetch_all($result4);
                    $data5 = mysqli_fetch_all($result5, MYSQLI_ASSOC);
                    $data6 = mysqli_fetch_assoc($result6);
                    $data7 = mysqli_fetch_all($result7);
                    $data8 = mysqli_fetch_assoc($result8);

                    foreach ($data5 as $key => $appointment) {
                        $data5[$key]['a_time'] = date('d M Y h:i A', $appointment['a_time']);
                    }

                    if($data6){
                        $data6['a_time'] = date('d M Y h:i A', $data6['a_time']);
                        $customerInfo = parseCustomerIC($data6['customeric']);
                        $data6['age'] = $customerInfo['age'];
                        $data6['sex'] = $customerInfo['sex'];
                    }

                    if($data8){
                        $data8['a_time'] = date('d M Y h:i A', $data8['a_time']);
                    }

                    echo json_encode([
                        "status" => "success",
                        "monthAppointments" => $data1['total'],
                        "todayAppointments" => $data2['total'],
                        "totalClient" => $data3['total'],
                        "reqestAppointments" => $data4,
                        "upcomingAppointments" => $data5,
                        "nextAppointments" => $data6,
                        "nextReason" => $data7,
                        "lastAppointment" => $data8

                    ]);
                } else {
                    echo json_encode([
                        "status" => "error",
                        "message" => "Failed to execute query."
                    ]);
                }

            // echo date('d M Y h:i A', time());
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