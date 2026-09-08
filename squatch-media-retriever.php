<?php
/*
Plugin Name: Squatch Media Retriever
Plugin URI: https://squatchcreative.com
Description: Retrieves featured images from a Squatch Post Export CSV and copies them to the local uploads directory.
Version: 1.005
Author: Squatch Creative
Author URI: https://squatchcreative.com
*/

$plugin_data = get_file_data(__FILE__,array('Version' => 'Version'));
$plugin_version = $plugin_data['Version'];

define('SQUATCH_RETRIEVER_PLUGIN', plugin_dir_url(__FILE__));
define('SQUATCH_RETRIEVER_PATH', plugin_dir_path(__FILE__));
define('SQUATCH_RETRIEVER_BATCH_SIZE',3);



add_action('admin_menu', 'squatch_media_retriever_menu');
function squatch_media_retriever_menu() {
	add_submenu_page(
		'tools.php',
		'Squatch Media Retriever',
		'Squatch Media Retriever',
		'manage_options',
		'squatch-media-retriever',
		'squatch_media_retriever_page'
	);
}



add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'squatch_media_retriever_settings_link');
function squatch_media_retriever_settings_link($links) {
	$url = admin_url('tools.php?page=squatch-media-retriever');
	$settings_link = '<a href="' . esc_url($url) . '">Settings</a>';
	array_unshift($links, $settings_link);
	return $links;
}



add_filter('admin_footer_text', 'squatch_admin_footer_text_retriever');
function squatch_admin_footer_text_retriever($footer_text) {
	$screen = get_current_screen();

	if($screen && $screen->id === 'tools_page_squatch-media-retriever') {
		$img_url = SQUATCH_RETRIEVER_PLUGIN . 'assets/built-by-squatch.svg';

		ob_start();
		?>
		<span id="footer-thankyou">
			<a href="https://squatchcreative.com" title="Built By Squatch Creative" target="_blank">
				<img src="<?php echo esc_url($img_url); ?>" alt="Built By Squatch Creative">
			</a>
		</span>
		<?php

		return ob_get_clean();
	}

	return $footer_text;
}



add_action('admin_head', 'squatch_media_retriever_admin_head');
function squatch_media_retriever_admin_head() {
	$screen = get_current_screen();

	if($screen && $screen->id === 'tools_page_squatch-media-retriever') {
		?>
		<style>
		.squatch-plugin-header {
			display: flex;
			gap: 18px;
			align-items: center;
			padding: 18px 0;
		}
		.squatch-plugin-header img {
			display: block;
			margin: 0;
			width: 54px;
			height: 54px;
			background: black;
			border-radius: 50%;
			padding: 2px;
		}
		.squatch-header-text * {
			margin: 0 !important;
			padding: 0 !important;
		}
		#squatch-plugin-progress {
			margin: 12px 0;
			background: #eee;
			border: 1px solid #ccc;
			height: 20px;
			width: 100%;
			position: relative;
			border-radius: 6px;
			overflow: hidden;
		}
		#squatch-plugin-bar {
			background: #0073aa;
			width: 0%;
			height: 100%;
			transition: width 0.3s ease;
		}
		#squatch-plugin-output {
			overflow: auto;
			max-height: 400px;
			background: #1d2327;
			padding: 18px;
			border-radius: 8px;
			color: white;
		}
		#squatch-plugin-output a {
			color: white;
			text-decoration: none;
		}
		#squatch-plugin-output strong {
			display: inline-block;
			color: #ffd747;
		}
		#squatch-plugin-summary {
			padding: 20px 0 48px 0;
			font-size: 16px;
			font-weight: bold;
		}
		#media_retriever_form {
			transition: 180ms ease all;
			position: relative;
			display: flex;
			gap: 12px;
			flex-flow: column;
			align-items: flex-start;
		}
		#media_retriever_form.processing {
			opacity: 0.6;
			pointer-events: none;
		}
		#media_retriever_form.processing button {
			cursor: not-allowed;
		}
		#media_retriever_form.processing::after {
			content: "\f463";
			font-family: dashicons;
			display: block;
			font-size: 24px;
			animation: MediaRetrieverSpin 1s linear infinite;
		}
		@keyframes MediaRetrieverSpin {
			from { transform: rotate(0deg); }
			to { transform: rotate(360deg); }
		}
		#media_retriever_form label {
			display: block;
			min-width: 100px;
		}
		#footer-thankyou img {
			height: 28px;
			vertical-align: middle;
		}
		.form-field {
			display: flex;
			gap: 18px;
			width: 560px;
			max-width: 100%;
		}
		.form-field input,
		.form-field select {
			display: block;
			flex: 1;
		}
		.form-field.checkbox-field {
			align-items: center;
		}
		.form-field.checkbox-field label {
			min-width: auto;
		}
		.form-field.checkbox-field input {
			flex: 0;
		}
		.retriever-description {
			color: #646970;
			font-size: 13px;
			margin-top: -6px;
			margin-left: 118px;
		}
		</style>
		<?php
	}
}



function squatch_media_retriever_page() {

	wp_enqueue_media();

	$img_url = SQUATCH_RETRIEVER_PLUGIN . 'assets/squatch-mark-yellow.svg';
	$webp_supported = squatch_media_retriever_webp_supported();

	echo '<div class="wrap">';

	echo '<div class="squatch-plugin-header">';
	echo '<img src="' . esc_url($img_url) . '" alt="Built By Squatch Creative">';
	echo '<div class="squatch-header-text">';
	echo '<h1>Squatch Media Retriever</h1>';
	echo '<p>Retrieves featured images from a <a href="https://github.com/RCNeil/squatch-post-exporter" target="_blank">Squatch Post Exporter</a> CSV and copies them to the local uploads directory. <a href="https://github.com/RCNeil/squatch-media-retriever" target="_blank">View Details</a></p>';
	echo '</div>';
	echo '</div>';

	echo '<form id="media_retriever_form">';

	echo '<div class="form-field">';
	echo '<label><strong>CSV File</strong></label>';
	echo '<input type="text" id="csv_file" name="csv_file" class="regular-text" readonly>';
	echo '<button type="button" class="button" id="select_csv">Select CSV</button>';
	echo '</div>';

	if($webp_supported) {
		echo '<div class="form-field checkbox-field">';
		echo '<label><strong>WebP</strong></label>';
		echo '<input type="checkbox" id="convert_webp" name="convert_webp" value="1" checked>';
		echo '<label for="convert_webp">Convert images to WebP when supported</label>';
		echo '</div>';

		echo '<div class="retriever-description">';
		echo 'Original images will be retained and the WebP version will be created alongside them.';
		echo '</div>';
	}

	echo '<input type="hidden" name="_nonce" value="' . esc_attr(wp_create_nonce('squatch_retriever_nonce')) . '">';

	echo '<button type="submit" class="button button-primary">Start Retrieval</button>';

	echo '</form>';

	echo '<div id="squatch-plugin-progress"><div id="squatch-plugin-bar"></div></div>';
	echo '<div id="squatch-plugin-output"></div>';
	echo '<div id="squatch-plugin-summary"></div>';

	echo '</div>';
	?>
	<script>
	jQuery(document).ready(function($) {
		var $form = $('#media_retriever_form');
		var $outputDiv = $('#squatch-plugin-output');
		var $progressBar = $('#squatch-plugin-bar');
		var $summaryDiv = $('#squatch-plugin-summary');
		var file_frame;

		$('#select_csv').on('click', function(e) {
			e.preventDefault();

			if(file_frame) {
				file_frame.open();
				return;
			}

			file_frame = wp.media({
				title: 'Select CSV File',
				button: { text: 'Use this file' },
				multiple: false,
				library: {
					type: 'text/csv'
				}
			});

			file_frame.on('select', function() {
				var attachment = file_frame.state().get('selection').first().toJSON();
				$('#csv_file').val(attachment.url);
			});

			file_frame.open();
		});

		$form.on('submit', function(e) {
			e.preventDefault();

			var csvFile = $('#csv_file').val();

			if(!csvFile) {
				alert('Please select a CSV file.');
				return;
			}

			$form.addClass('processing');
			$outputDiv.html('');
			$summaryDiv.html('');
			$progressBar.css('width', '0%');

			var start = 0;
			let retrieved = 0;
			let skipped = 0;
			let failed = 0;
			var convertWebp = $('#convert_webp').is(':checked') ? 1 : 0;

			function processBatch() {
				$.ajax({
					url: ajaxurl,
					method: 'POST',
					dataType: 'json',
					data: {
						action: 'squatch_retrieve_media',
						start: start,
						csv_file: csvFile,
						convert_webp: convertWebp,
						_nonce: '<?php echo wp_create_nonce("squatch_retriever_nonce"); ?>'
					},
					success: function(res) {
						if(res.success) {
							$outputDiv.append(res.data.output);
							$outputDiv.scrollTop($outputDiv[0].scrollHeight);

							var percent = 0;

							if(res.data.total > 0) {
								percent = Math.min(100, Math.round((res.data.next_start / res.data.total) * 100));
							}

							$progressBar.css('width', percent + '%');
							retrieved += parseInt(res.data.retrieved,10) || 0;
							skipped += parseInt(res.data.skipped,10) || 0;
							failed += parseInt(res.data.failed,10) || 0;

							if(!res.data.done) {
								start = res.data.next_start;
								processBatch();
							} else {
								$progressBar.css('width', '100%');

								$summaryDiv.html(
									'<strong>Retrieval complete!</strong> ' +
									'Retrieved: ' + retrieved + ' &bull; ' +
									'Skipped: ' + skipped + ' &bull; ' +
									'Failed: ' + failed
								);

								$form.removeClass('processing');
							}
						} else {
							$outputDiv.append('<p style="color:red;">' + (res.data.message || 'Error during retrieval') + '</p>');
							$form.removeClass('processing');
						}
					},
					error: function(xhr) {
						$outputDiv.append('<p style="color:red;">AJAX request failed. See console.</p>');
						console.error('AJAX error', xhr);
						$form.removeClass('processing');
					}
				});
			}

			processBatch();
		});
	});
	</script>
	<?php
}



add_action('wp_ajax_squatch_retrieve_media', 'squatch_retrieve_media');

function squatch_retrieve_media() {

	check_ajax_referer('squatch_retriever_nonce', '_nonce');

	if(!current_user_can('manage_options')) {
		wp_send_json_error(array(
			'message' => 'You do not have permission to perform this action.'
		));
	}

	$batch_size = SQUATCH_RETRIEVER_BATCH_SIZE;

	$start = isset($_POST['start']) ? intval($_POST['start']) : 0;
	$csv_file_url = isset($_POST['csv_file']) ? esc_url_raw($_POST['csv_file']) : '';
	$convert_webp = !empty($_POST['convert_webp']);

	if(empty($csv_file_url)) {
		wp_send_json_error(array(
			'message' => 'No CSV file provided.'
		));
	}

	$csv_file = squatch_retriever_get_local_csv_path($csv_file_url);

	if(!$csv_file || !file_exists($csv_file)) {
		wp_send_json_error(array(
			'message' => 'CSV file not found.'
		));
	}

	$csv_data = squatch_retriever_read_csv_batch($csv_file, $start, $batch_size);

	if(is_wp_error($csv_data)) {
		wp_send_json_error(array(
			'message' => $csv_data->get_error_message()
		));
	}

	$total = $csv_data['total'];

	if(empty($csv_data['header'])) {
		wp_send_json_error(array(
			'message' => 'The CSV file is empty or could not be read.'
		));
	}

	if(!in_array('Featured Image URL', $csv_data['header'], true)) {
		wp_send_json_error(array(
			'message' => 'The selected CSV does not contain a "Featured Image URL" column.'
		));
	}

	if($start >= $total) {
		wp_send_json_success(array(
			'output' => '',
			'total' => $total,
			'next_start' => $total,
			'done' => true,
			'retrieved' => 0,
			'skipped' => 0,
			'failed' => 0
		));
	}

	$output = '';
	$retrieved = 0;
	$skipped = 0;
	$failed = 0;

	foreach($csv_data['rows'] as $index => $row) {

		$row_number = $start + $index;
		$image_url = isset($row['Featured Image URL']) ? trim($row['Featured Image URL']) : '';

		if(empty($image_url)) {
			$output .= '#' . $row_number . ' <strong>SKIPPED:</strong> No Featured Image URL<br /><br />';
			$skipped++;
			continue;
		}

		$result = squatch_retriever_process_image($image_url, $convert_webp);

		if($result['status'] === 'retrieved') {
			$retrieved++;

			$output .= '#' . $row_number . ' <strong>RETRIEVED:</strong> ' . esc_html($result['filename']) . '<br />';
			$output .= 'Source: ' . esc_html($image_url) . '<br />';
			$output .= 'Destination: ' . esc_html($result['relative_path']) . '<br />';

			if(!empty($result['webp'])) {
				$output .= '&bull; <strong>WebP:</strong> ' . esc_html($result['webp']) . '<br />';
			}

			$output .= '<br />';

		} elseif($result['status'] === 'exists') {
			$skipped++;

			$output .= '#' . $row_number . ' <strong>SKIPPED (exists):</strong> ' . esc_html($result['filename']) . '<br />';
			$output .= 'Destination: ' . esc_html($result['relative_path']) . '<br />';

			if(!empty($result['webp'])) {
				$output .= '&bull; <strong>WebP:</strong> ' . esc_html($result['webp']) . '<br />';
			}

			$output .= '<br />';

		} elseif($result['status'] === 'webp_created') {
			$retrieved++;

			$output .= '#' . $row_number . ' <strong>WEBP CREATED:</strong> ' . esc_html($result['filename']) . '<br />';
			$output .= 'Destination: ' . esc_html($result['relative_path']) . '<br />';
			$output .= '&bull; <strong>WebP:</strong> ' . esc_html($result['webp']) . '<br /><br />';

		} else {
			$failed++;

			$output .= '#' . $row_number . ' <strong>FAILED:</strong> ' . esc_html($image_url) . '<br />';
			$output .= esc_html($result['message']) . '<br /><br />';
		}
	}

	$next_start = min($start + count($csv_data['rows']), $total);
	$done = $next_start >= $total;

	wp_send_json_success(array(
		'output' => $output,
		'total' => $total,
		'next_start' => $next_start,
		'done' => $done,
		'retrieved' => $retrieved,
		'skipped' => $skipped,
		'failed' => $failed
	));
}



function squatch_retriever_get_local_csv_path($csv_file_url) {

	$upload_dir = wp_upload_dir();

	$upload_base_url = trailingslashit($upload_dir['baseurl']);
	$upload_base_dir = trailingslashit($upload_dir['basedir']);

	if(strpos($csv_file_url, $upload_base_url) === 0) {
		$relative_path = substr($csv_file_url, strlen($upload_base_url));
		$relative_path = ltrim($relative_path, '/');

		return $upload_base_dir . $relative_path;
	}

	$site_url = trailingslashit(site_url('/'));

	if(strpos($csv_file_url, $site_url) === 0) {
		$relative_path = substr($csv_file_url, strlen($site_url));
		$relative_path = ltrim($relative_path, '/');

		return ABSPATH . $relative_path;
	}

	return false;
}



function squatch_retriever_read_csv_batch($csv_file, $start, $batch_size) {

	$handle = fopen($csv_file, 'r');

	if($handle === false) {
		return new WP_Error('csv_open_failed', 'Unable to open the CSV file.');
	}

	$header = fgetcsv($handle);

	if($header === false) {
		fclose($handle);

		return array(
			'header' => array(),
			'rows' => array(),
			'total' => 0
		);
	}

	$header = array_map('trim', $header);

	$total = 0;
	$rows = array();

	while(($data = fgetcsv($handle)) !== false) {

		$total++;

		if($total <= $start) {
			continue;
		}

		if(count($rows) >= $batch_size) {
			continue;
		}

		if(count($data) < count($header)) {
			$data = array_pad($data, count($header), '');
		}

		if(count($data) > count($header)) {
			$data = array_slice($data, 0, count($header));
		}

		$rows[] = array_combine($header, $data);
	}

	fclose($handle);

	return array(
		'header' => $header,
		'rows' => $rows,
		'total' => $total
	);
}



function squatch_retriever_process_image($image_url, $convert_webp = false) {

	$image_url = trim($image_url);

	if(empty($image_url) || !filter_var($image_url, FILTER_VALIDATE_URL)) {
		return array(
			'status' => 'failed',
			'message' => 'Invalid image URL.'
		);
	}

	$path = parse_url($image_url, PHP_URL_PATH);

	if(empty($path)) {
		return array(
			'status' => 'failed',
			'message' => 'Unable to determine image path from URL.'
		);
	}

	$uploads_marker = '/wp-content/uploads/';

	$uploads_position = strpos($path, $uploads_marker);

	if($uploads_position === false) {
		return array(
			'status' => 'failed',
			'message' => 'Image URL does not contain a WordPress uploads path.'
		);
	}

	$relative_path = substr($path, $uploads_position + strlen($uploads_marker));
	$relative_path = rawurldecode($relative_path);
	$relative_path = ltrim($relative_path, '/');

	if(empty($relative_path)) {
		return array(
			'status' => 'failed',
			'message' => 'Unable to determine destination image path.'
		);
	}

	$upload_dir = wp_upload_dir();
	$destination = trailingslashit($upload_dir['basedir']) . $relative_path;

	$destination_dir = dirname($destination);

	if(!wp_mkdir_p($destination_dir)) {
		return array(
			'status' => 'failed',
			'message' => 'Unable to create destination directory: ' . $destination_dir
		);
	}

	$extension = strtolower(pathinfo($destination, PATHINFO_EXTENSION));
	$webp_destination = preg_replace('/\.[^.]+$/', '.webp', $destination);

	/*
	 * If WebP is requested and the WebP already exists, consider this
	 * complete even if the original image does not exist locally.
	 */
	if($convert_webp && squatch_media_retriever_webp_supported() && file_exists($webp_destination)) {
		return array(
			'status' => 'exists',
			'filename' => basename($destination),
			'relative_path' => $relative_path,
			'webp' => str_replace(trailingslashit($upload_dir['basedir']), '', $webp_destination)
		);
	}

	/*
	 * If the original already exists, don't download it again.
	 * We can still attempt WebP conversion if requested.
	 */
	if(file_exists($destination) && filesize($destination) > 0) {

		$webp = '';

		if($convert_webp && squatch_media_retriever_webp_supported()) {
			if(squatch_retriever_create_webp($destination, $webp_destination)) {
				$webp = str_replace(trailingslashit($upload_dir['basedir']), '', $webp_destination);
			}
		}

		return array(
			'status' => 'exists',
			'filename' => basename($destination),
			'relative_path' => $relative_path,
			'webp' => $webp
		);
	}

	/*
	 * Retrieve the original image.
	 */
	$response = wp_safe_remote_get($image_url, array(
		'timeout' => 30,
		'redirection' => 5,
		'stream' => true,
		'filename' => $destination,
		'limit_response_size' => 25 * 1024 * 1024,
	));

	if(is_wp_error($response)) {
		return array(
			'status' => 'failed',
			'message' => $response->get_error_message()
		);
	}

	$response_code = wp_remote_retrieve_response_code($response);

	if($response_code < 200 || $response_code >= 300) {
		if(file_exists($destination)) {
			@unlink($destination);
		}

		return array(
			'status' => 'failed',
			'message' => 'Remote server returned HTTP ' . intval($response_code) . '.'
		);
	}

	if(!file_exists($destination) || filesize($destination) === 0) {
		if(file_exists($destination)) {
			@unlink($destination);
		}

		return array(
			'status' => 'failed',
			'message' => 'Image was not successfully downloaded.'
		);
	}

	/*
	 * Optionally create WebP.
	 */
	$webp = '';

	if($convert_webp && squatch_media_retriever_webp_supported()) {
		if(squatch_retriever_create_webp($destination, $webp_destination)) {
			$webp = str_replace(trailingslashit($upload_dir['basedir']), '', $webp_destination);
		}
	}

	return array(
		'status' => 'retrieved',
		'filename' => basename($destination),
		'relative_path' => $relative_path,
		'webp' => $webp
	);
}



function squatch_media_retriever_webp_supported() {

	if(function_exists('imagewebp') && function_exists('imagecreatefromjpeg') && function_exists('imagecreatefrompng')) {
		return true;
	}

	if(class_exists('Imagick')) {
		try {
			$imagick = new Imagick();
			$formats = $imagick->queryFormats('WEBP');
			$imagick->clear();
			$imagick->destroy();

			if(in_array('WEBP', $formats, true)) {
				return true;
			}
		} catch(Exception $e) {
			return false;
		}
	}

	return false;
}



function squatch_retriever_create_webp($source, $destination) {

	if(!file_exists($source)) {
		return false;
	}

	/*
	 * Try GD first.
	 */
	if(function_exists('imagewebp')) {
		$extension = strtolower(pathinfo($source, PATHINFO_EXTENSION));
		$image = false;
		switch($extension) {
			case 'jpg':
			case 'jpeg':
				if(function_exists('imagecreatefromjpeg')) {
					$image = @imagecreatefromjpeg($source);
				}
				break;

			case 'png':
				if(function_exists('imagecreatefrompng')) {
					$image = @imagecreatefrompng($source);

					if($image) {
						imagealphablending($image, false);
						imagesavealpha($image, true);
					}
				}
				break;
		}

		if($image) {
			$quality = 82;
			$result = @imagewebp($image, $destination, $quality);

			imagedestroy($image);

			if($result && file_exists($destination) && filesize($destination) > 0) {
				return true;
			}

			if(file_exists($destination)) {
				@unlink($destination);
			}
		}
	}

	/*
	 * Fall back to Imagick.
	 */
	if(class_exists('Imagick')) {
		try {
			$image = new Imagick();
			$image->readImage($source);

			$image->setImageFormat('webp');
			$image->setImageCompressionQuality(82);

			/*
			 * Flatten images with transparency onto a white background
			 * only when necessary.
			 */
			if($image->getImageAlphaChannel()) {
				$image->setImageAlphaChannel(Imagick::ALPHACHANNEL_ACTIVATE);
			}

			$result = $image->writeImage($destination);

			$image->clear();
			$image->destroy();

			if($result && file_exists($destination) && filesize($destination) > 0) {
				return true;
			}

			if(file_exists($destination)) {
				@unlink($destination);
			}

		} catch(Exception $e) {

			if(isset($image) && $image instanceof Imagick) {
				$image->clear();
				$image->destroy();
			}

			if(file_exists($destination)) {
				@unlink($destination);
			}
		}
	}

	return false;
}