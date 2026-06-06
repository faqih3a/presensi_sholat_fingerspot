<?php

	function getLog() {
		include 'include/global.php';
		$arr 	= array();

		try {
			$sql 	= 'SELECT * FROM t_log ORDER BY created_at DESC';
			$stmt 	= $conn->query($sql);
			while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
				$arr[] = array(
					'cloud_id'		=> $row['cloud_id'],
					'type'			=> $row['type'],
					'created_at'	=> $row['created_at'],
					'original_data'	=> $row['original_data']
				);
			}
		} catch (PDOException $e) {
			// Query failed
		}

		return $arr;

	}

?>