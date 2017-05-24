<?php
	//資料庫設定
	//資料庫位置
	$db_server = "localhost";
	//資料庫名稱
	$db_name = "musicdaf_maindb";
	//資料庫管理者帳號
	$db_user = "musicdaf_root";
	//資料庫管理者密碼
	$db_passwd = "v15uK78jvQ";

	$mysqli = new mysqli($db_server, $db_user, $db_passwd, $db_name);
	
	$GLOBALS['mysqli'] = $mysqli;
	
	/* 對資料庫連線 */
	if ($mysqli->connect_errno) {
		printf("無法對資料庫連線: %s\n", $mysqli->connect_error);
		exit();
	}
	if (!$mysqli->set_charset("utf8mb4")) {
		printf("Error loading character set utf8mb4: %s\n", $mysqli->error);
		exit();
	}
?>