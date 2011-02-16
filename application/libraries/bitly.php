<?php
	class bitly {

		private $_api_url;
		private $_api_key;
		private $_api_secret;
		private $_errors = array();
		private $_enable_debug = FALSE;

		function __construct()
		{
			$this->_obj =& get_instance();
			$this->_obj->load->config('bitly');

			$this->_api_url 	= $this->_obj->config->item('bitly_api_url');
			$this->_api_user 	= $this->_obj->config->item('bitly_api_user');
			$this->_api_key 	= $this->_obj->config->item('bitly_api_key');

			$this->connection = new bitlyConnection();
		}

		public function call($uri, $data = array())
		{
			$response = FALSE;

			try
			{
				$response = $this->connection->get($this->append_token($this->_api_url.$uri), $data);
			}
			catch (bitlyException $e)
			{
				$this->_errors[] = $e;

				if ( $this->_enable_debug )
				{
					echo $e;
				}
			}

			return $response;
		}

		public function errors()
		{
			return $this->_errors;
		}

		public function last_error()
		{
			if ( count($this->_errors) == 0 ) return NULL;

			return $this->_errors[count($this->_errors) - 1];
		}

		public function append_token($url)
		{
			return $url.'?format=json&login='.$this->_api_user.'&apiKey='.$this->_api_key.'&';
		}


		public function enable_debug($debug = TRUE)
		{
			$this->_enable_debug = (bool) $debug;
		}
	}

	class bitlyConnection {

		// Allow multi-threading.

		private $_mch = NULL;
		private $_properties = array();

		function __construct()
		{
			$this->_mch = curl_multi_init();

			$this->_properties = array(
				'code' 		=> CURLINFO_HTTP_CODE,
				'time' 		=> CURLINFO_TOTAL_TIME,
				'length'	=> CURLINFO_CONTENT_LENGTH_DOWNLOAD,
				'type' 		=> CURLINFO_CONTENT_TYPE
			);
		}

		private function _initConnection($url)
		{
			$this->_ch = curl_init($url);
			curl_setopt($this->_ch, CURLOPT_RETURNTRANSFER, TRUE);
		}

		public function get($url, $params = array())
		{
			if ( count($params) > 0 )
			{
				foreach( $params as $k => $v )
				{
					$url .= "{$k}={$v}&";
				}

				$url = substr($url, 0, -1);
			}

			$this->_initConnection($url);
			$response = $this->_addCurl($url, $params);

		    return $response;
		}

		private function _addCurl($url, $params = array())
		{
			$ch = $this->_ch;

			$key = (string) $ch;
			$this->_requests[$key] = $ch;

			$response = curl_multi_add_handle($this->_mch, $ch);

			if ( $response === CURLM_OK || $response === CURLM_CALL_MULTI_PERFORM )
			{
				do {
					$mch = curl_multi_exec($this->_mch, $active);
				} while ( $mch === CURLM_CALL_MULTI_PERFORM );

				return $this->_getResponse($key);
			}
			else
			{
				return $response;
			}
		}

		private function _getResponse($key = NULL)
		{
			if ( $key == NULL ) return FALSE;

			if ( isset($this->_responses[$key]) )
			{
				return $this->_responses[$key];
			}

			$running = NULL;

			do
			{
				$response = curl_multi_exec($this->_mch, $running_curl);

				if ( $running !== NULL && $running_curl != $running )
				{
					$this->_setResponse($key);

					if ( isset($this->_responses[$key]) )
					{
						$response = new bitlyResponse( (object) $this->_responses[$key] );

						if ( $response->__resp->code !== 200 )
						{
							$error = $response->__resp->code.' | Request Failed';

							if ( isset($response->__resp->data->error->type) )
							{
								$error .= ' - '.$response->__resp->data->error->type.' - '.$response->__resp->data->error->message;
							}

							throw new bitlyException($error);
						}

						return $response;
					}
				}

				$running = $running_curl;

			} while ( $running_curl > 0);

		}

		private function _setResponse($key)
		{
			while( $done = curl_multi_info_read($this->_mch) )
			{
				$key = (string) $done['handle'];
				$this->_responses[$key]['data'] = curl_multi_getcontent($done['handle']);

				foreach ( $this->_properties as $curl_key => $value )
				{
					$this->_responses[$key][$curl_key] = curl_getinfo($done['handle'], $value);

					curl_multi_remove_handle($this->_mch, $done['handle']);
				}
		  }
		}
	}

	class bitlyResponse {

		private $__construct;

		public function __construct($resp)
		{
			$this->__resp = $resp;

			$data = json_decode($this->__resp->data);

			if ( $data !== NULL )
			{
				$this->__resp->data = $data;
			}
		}

		public function __get($name)
		{
			if ($this->__resp->code < 200 || $this->__resp->code > 299) return FALSE;

			$result = array();

			if ( is_string($this->__resp->data ) )
			{
				parse_str($this->__resp->data, $result);
				$this->__resp->data = (object) $result;
			}

			if ( $name === '_result')
			{
				return $this->__resp->data;
			}
			
			return $this->__resp->data->$name;
		}
	}

	class bitlyException extends Exception {

		function __construct($string)
		{
			parent::__construct($string);
		}

		public function __toString() {
			return "exception '".__CLASS__ ."' with message '".$this->getMessage()."' in ".$this->getFile().":".$this->getLine()."\nStack trace:\n".$this->getTraceAsString();
		}
	}