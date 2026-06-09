<?php

	function getLog() {
		include 'include/global.php';
		$sql 	= 'SELECT * FROM t_log ORDER BY created_at DESC';
		$result	= mysqli_query($conn, $sql);
		$arr 	= array();
		$i 	= 0;

		while ($row = mysqli_fetch_array($result)) {

			$arr[$i] = array(
				'cloud_id'		=> $row['cloud_id'],
				'type'			=> $row['type'],
				'created_at'	=> $row['created_at'],
				'original_data'	=> $row['original_data']
			);

			$i++;

		}

		return $arr;

	}

?>