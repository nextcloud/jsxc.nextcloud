<?php

namespace OCA\OJSXC;

class DataRetriever implements IDataRetriever
{
	public function fetchUrl($url, $data = [])
	{
		$context = stream_context_create(['http' =>
		  [
			  'method' => 'POST',
			  'ignore_errors' => '1',
			  'header' => 'Content-type: application/x-www-form-urlencoded',
			  'content' => http_build_query($data),
			  'timeout' => 120,
		  ]
	  ]);

		$body = file_get_contents($url, false, $context);
		$headers = [];

		if ($body !== false) {
			$headers = $this->parseHeaders($http_response_header);
		}

		return [
		 'body' => $body,
		 'headers' => $headers
	  ];
	}

	private function parseHeaders($headers)
	{
		$head = [];

		foreach ($headers as $k => $v) {
			$t = explode(':', $v, 2);
			if (isset($t[1])) {
				$head[ trim($t[0]) ] = trim($t[1]);
			} else {
				$head[] = $v;
				$out = [];
				if (preg_match('#HTTP/[0-9\.]+\s+([0-9]+)#', $v, $out)) {
					$head['response_code'] = intval($out[1]);
				}
			}
		}

		return $head;
	}
}
