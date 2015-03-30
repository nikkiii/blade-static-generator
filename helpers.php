<?php

if (!function_exists('view')) {
	function view($name) {
		return $name . '.html';
	}
}