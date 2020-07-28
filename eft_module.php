<?php

/**
 * Ensures that the module init file can't be accessed directly, only within the application.
 */
defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: Instant EFT Payment Gateway Perfex CRM Module
Description: Instant EFT Payment Gateway.
Version: 1.0.0
Requires at least: 1.0.*
*/
register_payment_gateway('eft_gateway', 'eft_module');
// register_activation_hook('eft_module', 'module_init');

// function module_init(){
// 	$controller = FCPATH."modules/eft_module/controllers/Eft.php";
// 	$des_controller = APPPATH."controllers/gateways/Eft.php";

// 	if(file_exists($controller)){
// 		$status = @copy($controller, $des_controller);
// 		@chmod($des_controller, 0777);
// 	}
// }