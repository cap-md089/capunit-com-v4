<?php
	class Output {
		public static function doPost ($e, $c, $l, $m, $a) {
			if (!$l) return ['error' => 411];

			$fname = $e['form-data']['filesList'][0];

			$file = $e['form-data'][$fname][0];

			if ($file['error'] == UPLOAD_ERR_INI_SIZE) {
				return [
					'headers' => [
						'X-Error' => 'File Upload too big'
					],
					'body' => print_r($file, true)
				];
			}

			$data = file_get_contents($file['tmp_name']);
			$fname = $file['name'];

			$f = explode(".", $file['name']);
			$end = $f[count($f)-1];
			$file = tmpfile();
			fwrite($file, $data);
			$metaDatas = stream_get_meta_data($file);
			$uri = $metaDatas['uri'];
			rename($uri, $uri.'.'.$end);
			$uri .= '.'.$end;
			$ctype = mime_content_type($uri);
			fclose($file);
			
			$file = File::Create($fname, $data, $m);

			if (in_array($ctype, [
				'image/jpg',
				'image/jpeg',
				'image/pjpeg',
				'image/pjpg',
			])) {
				$data = @exif_read_data($uri);
				if (isset($data['DateTime'])) {
					$timestamp = strtotime($data['DateTime']);
				} else if (isset($data['DateTimeOriginal'])) {
					$timestamp = strtotime($data['DateTimeOriginal']);
				} else {
					$timestamp = time();
				}
				$file->Created = $timestamp;
			}
			if (in_array($ctype, [		
				'image/jpg',
				'image/jpeg',
				'image/pjpeg',
				'image/pjpg',
				'image/png',
				'image/gif'
			])) {
				$file->IsPhoto = true;
			}

			$file->save();
			$id = $file->ID;
			unset($file);
			return $id;
		}
	}
