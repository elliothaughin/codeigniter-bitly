<?php

	class bitly_test extends CI_Controller {
		
		function __construct()
		{
			parent::__construct();
		}
		
		function index()
		{
			// $this->load->add_package_path('/Users/elliot/sites/github/codeigniter-bitly/application/');
			$this->load->library('bitly');
			$this->bitly->enable_debug(TRUE);
			
			$longUrl = 'http://www.haughin.com/code/';
			
			$request_shorten = $this->bitly->call('shorten', array('longUrl' => $longUrl));
			$this->_dump($request_shorten->data);
			
			$request_expand = $this->bitly->call('expand', array('shortUrl' => $request_shorten->data->url));
			$this->_dump($request_expand->data);
			
			$request_info = $this->bitly->call('info', array('shortUrl' => $request_shorten->data->url));
			$this->_dump($request_info->data);
			
			$request_global_info = $this->bitly->call('info', array('hash' => $request_shorten->data->global_hash));
			$this->_dump($request_global_info->data);
			
			$request_clicks = $this->bitly->call('clicks', array('shortUrl' => $request_shorten->data->url));
			$this->_dump($request_clicks->data);
			
			$request_global_clicks = $this->bitly->call('clicks', array('hash' => $request_shorten->data->global_hash));
			$this->_dump($request_global_clicks->data);
			
			$request_referrers = $this->bitly->call('referrers', array('shortUrl' => $request_shorten->data->url));
			$this->_dump($request_referrers->data);
			
			$request_global_referrers = $this->bitly->call('referrers', array('hash' => $request_shorten->data->global_hash));
			$this->_dump($request_global_referrers->data);
			
			$request_countries = $this->bitly->call('countries', array('shortUrl' => $request_shorten->data->url));
			$this->_dump($request_countries->data);
			
			$request_global_countries = $this->bitly->call('countries', array('hash' => $request_shorten->data->global_hash));
			$this->_dump($request_global_countries->data);
			
			$request_clicks_min = $this->bitly->call('clicks_by_minute', array('shortUrl' => $request_shorten->data->url));
			$this->_dump($request_clicks_min->data);
			
			$request_global_clicks_min = $this->bitly->call('clicks_by_minute', array('hash' => $request_shorten->data->global_hash));
			$this->_dump($request_global_clicks_min->data);
			
			$request_clicks_day = $this->bitly->call('clicks_by_day', array('shortUrl' => $request_shorten->data->url));
			$this->_dump($request_clicks_day->data);
			
			$request_global_clicks_day = $this->bitly->call('clicks_by_day', array('hash' => $request_shorten->data->global_hash));
			$this->_dump($request_global_clicks_day->data);
		}
		
		private function _dump($data)
		{
			echo '<pre>';
			var_dump($data);
			echo '</pre>';
		}
	}