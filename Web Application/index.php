<?php

// Define the IoT_3D_printer_tracker class and its functions:
class IoT_3D_printer_tracker {
	public $token, $web_path, $conn, $table;
	
	public function __init__($token, $server, $conn, $table){
		$this->token = $token;
		$this->web_path = $server.$token;
		$this->conn = $conn;
		$this->table = $table;
	}
	
	// Telegram:
	public function send_message($chat_id, $string){
		$new_message = $this->web_path."/sendMessage?chat_id=".$chat_id."&text=".urlencode($string);
		file_get_contents($new_message);
	}
	
	public function send_photo($chat_id, $photo, $caption){
	    $new_photo = $this->web_path."/sendPhoto?chat_id=".$chat_id."&photo=".$photo."&caption=".$caption;
	    file_get_contents($new_photo);
	}
	
    // Database -> Insert Data:
	public function insert_new_data($x_axis, $y_axis, $z_axis, $date){
		$sql = "INSERT INTO `$this->table`(`x_axis`, `y_axis`, `z_axis`, `date`) VALUES ('$x_axis', '$y_axis', '$z_axis', '$date')";
		mysqli_query($this->conn, $sql);
	}

	// Database -> Retrieve Data:
	public function get_data_from_database(){
		$sql = "SELECT * FROM `$this->table` ORDER BY id DESC LIMIT 1";
		$result = mysqli_query($this->conn, $sql);
		if($row = mysqli_fetch_assoc($result)){
			return $row;
		}
	}
	
	// Database -> Create Table
	public function database_create_table(){
		// Create a new database table.
		$sql_create = "CREATE TABLE `$this->table`(
						id int AUTO_INCREMENT PRIMARY KEY NOT NULL,
						x_axis varchar(255) NOT NULL,
					    y_axis varchar(255) NOT NULL,
						z_axis varchar(255) NOT NULL,
						`date` varchar(255) NOT NULL
					   );";
		if(mysqli_query($this->conn, $sql_create)) echo("<br><br>Database Table Created Successfully!");
		// Insert the default data items as the first row into the given database table.
		$sql_insert = "INSERT INTO `$this->table`(`x_axis`, `y_axis`, `z_axis`, `date`) VALUES ('0,0', '0,0', '0,0', 'default')";
		if(mysqli_query($this->conn, $sql_insert)) echo("<br><br>Default Data Items Inserted Successfully!");
	}
}

// Define database and server settings:
$server = array(
	"name" => "localhost",
	"username" => "root",
	"password" => "bot",
	"database" => "telegram3dprinter",
	"table" => "entries"

);

$conn = mysqli_connect($server["name"], $server["username"], $server["password"], $server["database"]);

// Define the new 'printer_tracker' object:
$printer_tracker = new IoT_3D_printer_tracker();
$bot_token = "<_____________________>"; // e.g., 123456:ABC-DEF1234ghIkl-zyx57W2v1u123ew11
$printer_tracker->__init__($bot_token, "https://api.telegram.org/bot", $conn, $server["table"]); 


// Get updates on the 3D printer's lateral and vertical motions (X-axis, Y-axis, and Z-axis) from Raspberry Pi Pico so as to detect potential malfunctions.
if(isset($_POST["x_axis"]) && isset($_POST["y_axis"]) && isset($_POST["z_axis"])){
	// Obtain the latest printer motions stored in the given database table.
	$row = $printer_tracker->get_data_from_database();
	$previous_motions = [
		"x_axis" => [intval(explode(",", $row["x_axis"])[0]), intval(explode(",", $row["x_axis"])[1])],
		"y_axis" => [intval(explode(",", $row["y_axis"])[0]), intval(explode(",", $row["y_axis"])[1])],
		"z_axis" => [intval(explode(",", $row["z_axis"])[0]), intval(explode(",", $row["z_axis"])[1])]
	];
	// Obtain the current printer motions transferred by Raspberry Pi Pico.
	$motions = [
		"x_axis" => [intval(explode(",", $_POST["x_axis"])[0]), intval(explode(",", $_POST["x_axis"])[1])],
		"y_axis" => [intval(explode(",", $_POST["y_axis"])[0]), intval(explode(",", $_POST["y_axis"])[1])],
		"z_axis" => [intval(explode(",", $_POST["z_axis"])[0]), intval(explode(",", $_POST["z_axis"])[1])]
	];
	
	// Send the current printer motions to the given Telegram bot. 
	$chat_id = ""; // 1496498083
	$date = date("Y/m/d__h:i:s A");
	$printer_tracker->send_message($chat_id, "📌 Recent Printer Movements:\n\n ⌛ $date\n\n ➡️ X-Axis: ".$_POST["x_axis"]."\n\n ⬇️ Y-Axis: ".$_POST["y_axis"]."\n\n ⬆️ Z-Axis: ".$_POST["z_axis"]);
	// Check for potential malfunctions related to the printer's lateral or vertical motion.
	if($previous_motions["x_axis"][0] == $motions["x_axis"][0] && $previous_motions["x_axis"][1] == $motions["x_axis"][1] && $previous_motions["y_axis"][0] == $motions["y_axis"][0] && $previous_motions["y_axis"][1] == $motions["y_axis"][1] && $previous_motions["z_axis"][0] == $motions["z_axis"][0] && $previous_motions["z_axis"][1] == $motions["z_axis"][1]){
		$printer_tracker->send_message($chat_id, "⛔ 3D Printer is not working!");
	}else{
		if($previous_motions["x_axis"][0] == $motions["x_axis"][0] && $previous_motions["x_axis"][1] == $motions["x_axis"][1]){
			$printer_tracker->send_message($chat_id, "⚠️ X-Axis: Potential Malfunction Detected!");
		}
		if($previous_motions["y_axis"][0] == $motions["y_axis"][0] && $previous_motions["y_axis"][1] == $motions["y_axis"][1]){
			$printer_tracker->send_message($chat_id, "⚠️ Y-Axis: Potential Malfunction Detected!");
		}
		if($previous_motions["z_axis"][0] == $motions["z_axis"][0] && $previous_motions["z_axis"][1] == $motions["z_axis"][1]){
			$printer_tracker->send_message($chat_id, "⚠️ Z-Axis: Potential Malfunction Detected!");
		}
	}
	
	// Send a schematic describing 3D printer motions.
	$printer_tracker->send_photo($chat_id, "https://ars.els-cdn.com/content/image/3-s2.0-B9780128145647000031-f03-03-9780128145647.jpg", "3D Printer Motions");
	
	// Save the current printer motions to the given database table.
	$printer_tracker->insert_new_data($_POST["x_axis"], $_POST["y_axis"], $_POST["z_axis"], $date);
	echo("Data Saved and Transferred to the Telegram Bot Successfully!");
	
}else{
	echo("Waiting for new data...");
}

// If requested, create a new database table, including the default data items in the first row.
if(isset($_GET["create_table"]) && $_GET["create_table"] == "OK") $printer_tracker->database_create_table();

?>