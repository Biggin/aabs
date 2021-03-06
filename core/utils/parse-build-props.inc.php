<?php
/*
 * Copyright (C) 2017-2018 Lukas Berger <mail@lukasberger.at>
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

function parse_build_props($build_prop) {
	$properties = array( );

	if (preg_match_all("/([a-zA-Z0-9\.\-\_]*)\=(.*)/", $build_prop, $prop_matches)) {
		$match_count = count($prop_matches[0]);
		for ($i = 0; $i < $match_count; $i++) {
			$key   = $prop_matches[1][$i];
			$value = $prop_matches[2][$i];

			$properties[$key] = $value;
		}
	}

	return $properties;
}
