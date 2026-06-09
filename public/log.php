<?php
    	include 'include/global.php';
    	include 'include/function.php';

	if (isset($_GET['action']) && $_GET['action'] == 'index') {
?>
		<script type="text/javascript">

			$('title').html('Log');

		</script>
<?php

		$log = getLog();

		if (count($log) > 0) {

			echo	"<div class='row'>"
					."<div class='col-md-12'>"
						."<table class='table table-bordered table-hover'>"
								."<thead>"
									."<tr>"
										."<th class='col-md-4' style='text-align: center;'>Cloud ID</th>"
										."<th class='col-md-4' style='text-align: center;'>Type</th>"
										."<th class='col-md-4' style='text-align: center;'>Created At</th>"
										."<th class='col-md-4' style='text-align: center;'>Original Data</th>"
									."</tr>"
								."</thead>"
								."<tbody>";

			foreach ($log as $row) {

				echo					"<tr>"
				 					."<td>".$row['cloud_id']."</td>"
				 					."<td>".$row['type']."</td>"
				 					."<td>".$row['created_at']."</td>"
				 					."<td><code>".$row['original_data']."</code></td>"
				 					."</tr>";

			}

			echo
								"</tbody>"
						."</table>"
					."</div>"
				."</div>";

		} else {

			echo 'Log Empty';

		}

	}
?>