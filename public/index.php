<?php

// Check if the request is for the webhook page
$requestUri = $_SERVER['REQUEST_URI'] ?? '';
$path = parse_url($requestUri, PHP_URL_PATH);

if (preg_match('#/webhook/?(index\.php)?$#i', $path) || isset($_GET['webhook'])) {
    // Run the webhook index page content.
    include 'include/global.php';
    ?>
<!DOCTYPE html>
<!--[if IE 8]> <html lang="en" class="ie8 no-js"> <![endif]-->
<!--[if IE 9]> <html lang="en" class="ie9 no-js"> <![endif]-->
<!--[if !IE]><!-->
<html lang="en">
<!--<![endif]-->
	<head>
		<?php include 'include/head.php'; ?>
	</head>
	<body>
		<nav class="navbar navbar-default navbar-fixed-top" role="navigation">
			<div class="container">
				<div class="navbar-header">
					<button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
					<span class="sr-only">Toggle navigation</span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
					</button>
					<a class="navbar-brand" href="#">Webhook Developer Fingerspot.iO</a>
				</div>
				<div id="navbar" class="collapse navbar-collapse">
					<ul class="nav navbar-nav">
						<li><a href="#" onclick="load('<?php echo $base_path?>log.php?action=index')">Log</a></li>
						<li><a href="<?php echo $base_path?>documentation/index.html" target="_blank">Documentation</a></li>
					</ul>
				</div><!--/.nav-collapse -->
			</div>
		</nav>
		<div class="container">
			<div class="row">
				<div class="col-md-12 text-center">
					<h2><code>How it works?</code></h2>
					<iframe width="560" height="315" src="https://www.youtube.com/embed/9EL1C_-akvQ?showinfo=0&amp;wmode=opaque" frameborder="0" gesture="media" allowfullscreen></iframe>
				</div>
			</div>
			<br>
			<div class="row">
				<div class="col-md-12">
					<div id="content">

					</div>
				</div>
			</div>
		</div>

	<script>
		jQuery(document).ready(function() {

			console.log('ready to use...');

			load('<?php echo $base_path?>log.php?action=index');

		});
	</script>
	</body>
</html>
    <?php
    exit;
}

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
(require_once __DIR__.'/../bootstrap/app.php')
    ->handleRequest(Request::capture());
