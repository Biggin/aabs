<?php
/*
 * Copyright (C) 2017 Lukas Berger <mail@lukasberger.at>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
 
class UploadTask extends Thread {

	private $rom;
	private $short_device;
	private $device;
	private $file_match;
	private $type;

	public function __construct($rom, $short_device, $device, $file_match, $type) {
		$this->rom = $rom;
		$this->short_device = $short_device;
		$this->device = $device;
		$this->file_match = $file_match;
		$this->type = $type;
	}

	public function run() {
		// check if device is disabled
		if (AABS_DEVICES != "*" && strpos(AABS_DEVICES . ",", "{$this->device},") === false) {
			return;
		}

		$source_dir  = AABS_SOURCE_BASEDIR . "/{$this->rom}";
		$output_dir  = get_output_directory($this->rom, $this->device, $source_dir);
		$output_name = trim(shell_exec("/bin/bash -c \"basename {$output_dir}/{$this->file_match}\""), "\n\t");
		$output_path = dirname("{$output_dir}/{$this->file_match}") . "/" . $output_name;

		if (AABS_IS_DRY_RUN)
			echo "uploading build-output for '{$this->device}'\n";

		if (!AABS_IS_DRY_RUN) {
			if (!is_file($output_path)) {
				die("Output not found: \"{$output_path}\"\n");
			}

			$build_prop = file_get_contents("{$output_dir}/system/build.prop");

			$upload_dir = do_path_variables($this->rom, $this->device, $this->short_device, $this->type, AABS_UPLOAD_DIR, $build_prop);
			$upload_file = do_path_variables($this->rom, $this->device, $this->short_device, $this->type, AABS_UPLOAD_FILE, $build_prop);
		}

		$hash_methods = explode(",", AABS_HASH_METHODS);
		$hash_files = array( );

		foreach ($hash_methods as $hash_method) {
			$hash_method = trim($hash_method, " ");
			$hash_path   = "{$output_dir}/{$output_name}.aabs.{$hash_method}sum";

			if (AABS_IS_DRY_RUN)
				echo " - generating {$hash_method}sum\n";

			if (!AABS_IS_DRY_RUN) {
				echo "Generating {$hash_method}sum...\n";
				$out_hash = hash_file($hash_method, $output_path);
				file_put_contents($hash_path, "{$out_hash}  {$upload_file}");

				$hash_files[$hash_method] = $hash_path;
			}
		}

		if (AABS_IS_DRY_RUN)
			return;

		$fn = "";
		$params = array( );
		switch (AABS_UPLOAD_TYPE) {
			case "sftp":
				$fn = "upload_to_sftp";
				$params = array(
					'remote' => array(
						'host' => AABS_UPLOAD_HOST,
						'port' => AABS_UPLOAD_PORT,
						'user' => AABS_UPLOAD_USER,
						'pass' => AABS_UPLOAD_PASS,
					),
				);
				break;

			case "ftp":
				$fn = "upload_to_ftp";
				$params = array(
					'remote' => array(
						'host' => AABS_UPLOAD_HOST,
						'port' => AABS_UPLOAD_PORT,
						'user' => AABS_UPLOAD_USER,
						'pass' => AABS_UPLOAD_PASS,
					),
				);
				break;

			case "mega":
				$fn = "upload_to_mega";
				$params = array(
					'remote' => array(
						'user' => AABS_UPLOAD_USER,
						'pass' => AABS_UPLOAD_PASS,
					),
				);
				break;

			case "local":
				$fn = "upload_to_local";
				$params = array( );
				break;
		}

		$params['output'] = $output_path;
		$params['hashes'] = $hash_files;
		$params['upload'] = array(
			'dir'  => $upload_dir,
			'file' => $upload_file,
		);

		$fn($params);
	}

}