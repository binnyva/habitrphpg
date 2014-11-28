@@ -75,7 +75,12 @@ class HabitRPHPG {
		curl_setopt($ch, CURLOPT_HTTPHEADER, $custom_headers);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // ignorates certificate error
		$response = curl_exec($ch);

        // output error message if an error is occured
        if($response === false) die(curl_error($ch));

		curl_close($ch);

		if($this->options['debug']) {
@@ -157,7 +162,7 @@ class HabitRPHPG {
	}

	function updateTask($task_id, $data) {
		return $this->_request("put", "user/task/$task_id", $data);	
		return $this->_request("put", "user/task/$task_id", $data);
	}

	/**
