<?php

/** 

 * Plugin Name: Postcode Shipping 

 * Description: This plugin allows you to set a flat shipping rate per country or state or postcode per Quantity/Order on WooCommerce.

 * Version: 2.1.2

 * Author: Rizwan Ahammad

 * Text Domain: woocommerce_flatrate_perpostcode		

 * Domain Path: /lang

**/



if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly



/**

 * Check if WooCommerce is active

 **/

if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

	

	

	function woocommerce_flatrate_perpostcode_init() {

		

		if ( ! class_exists( 'WC_Flat_Rate_Per_Country_State_Postcode' ) ) {

		class WC_Flat_Rate_Per_Country_State_Postcode extends WC_Shipping_Method {

			/**

			 * Constructor for your shipping class

			 *

			 * @access public

			 * @return void

			 */

			public function __construct() {

				$this->id					= 'woocommerce_flatrate_perpostcode';

            	load_plugin_textdomain($this->id, false, dirname(plugin_basename(__FILE__)) . '/lang/');

				$this->method_title			= __('Postcode Shipping', $this->id);

				$this->method_description	= __('Postcode shipping rates let you define a standard rate per order.<br/><br/>if you set a rate for client\'s Zip/postcode it will be Apply.</br> if you set a rate for client\'s state it will be Apply.<br/>If you set a rate for the client\'s country it will be Apply.  Otherwise,</br> If none of the rates are set, the "Rest of the World" rate will be Apply.<br/><br/> Now you can add Shipping Rates Based on Quantity <br/> You can add Shipping Rates between 2 postcode zones', $this->id);

				$this->wc_shipping_init();

        		$this->init_shipping_fields_per_country();

				$this->init_shipping_fields_per_state();

				$this->init_shipping_fields_per_postcode();

			}



			/* Init the settings */

			function wc_shipping_init() {

				//Let's sort arrays the right way

				setlocale(LC_ALL, get_locale());

				//Regions - Source: http://www.geohive.com/earth/gen_codes.aspx

				
				// Load the settings API

				$this->init_form_fields(); // This is part of the settings API. Override the method to add your own settings

				$this->init_settings(); // This is part of the settings API. Loads settings you previously init.



				$this->title = $this->settings['title'];

				$this->enabled = $this->settings['enabled'];



				// Save settings in admin if you have any defined

				add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );



			}



			/* The Shipping Fields */

			function init_form_fields() {

				$fields = array(


					'enabled' => array(

						'title' 		=> __('Enable/Disable', 'woocommerce'),

						'type' 			=> 'checkbox',

						'label' 		=> __('Enable this shipping method', 'woocommerce'),

						'default' 		=> 'no',

					),
					
					'tax_status' => array(

						'title' 		=> __('Tax Status', 'woocommerce'),

						'type' 			=> 'select',

						'description' 	=> '',

						'default' 		=> 'taxable',

						'options'		=> array(

								'taxable' 	=> __('Taxable', 'woocommerce'),

								'none' 		=> __('None', 'woocommerce'),

							),

					),

					'title' => array(

						'title' 		=> __('Method Title', 'woocommerce'),

						'type' 			=> 'text',

						'description' 	=> __('This controls the title which the user sees during checkout.', 'woocommerce'),

						'default'		=> __('Postcode Shipping', $this->id),

					),
					'enable_restofworld' => array(

						'title' 		=> __('Rest Of World', 'woocommerce'),

						'type' 			=> 'checkbox',

						'label' 		=> __('Enable this rest of world cost', 'woocommerce'),

						'default' 		=> 'no',

					),

					'fee_world' => array(

						'title' 		=> __('Rest Of The World Cost', $this->id).' ('.get_woocommerce_currency().')',

						'type' 			=> 'text',

						'description'	=> __('The shipping fee for all the Countries/states/postcodes not specified bellow.', $this->id),

						'default'		=> '',

						'placeholder'	=>	'0.0',

					),
					
					'country_title' => array(

						'title' 		=> __('Country Method Title', 'woocommerce'),

						'type' 			=> 'text',

						'description' 	=> __('This controls the title which the user sees during checkout.', 'woocommerce'),

						'default'		=> __('Postcode Shipping(country)', $this->id),

					),

					'per_country_count' => array(

						'title' 		=> __('Number of Country rules', $this->id),

						'type' 			=> 'number',

						'description'	=> __('How many different "per country" rates do you want to set?', $this->id).' '.__('Please save the options after changing this value.', $this->id),

						'default'		=> 0,

					),
					
					'state_title' => array(

						'title' 		=> __('State Method Title', 'woocommerce'),

						'type' 			=> 'text',

						'description' 	=> __('This controls the title which the user sees during checkout.', 'woocommerce'),

						'default'		=> __('Postcode Shipping(state)', $this->id),

					),

					'per_state_count' => array(

						'title' 		=> __('Number of State rules', $this->id),

						'type' 			=> 'number',

						'description'	=> __('How many different "per state" rates do you want to set?', $this->id).' '.__('Please save the options after changing this value.', $this->id),

						'default'		=> 0,

					),
					
					'postcode_title' => array(

						'title' 		=> __('Postcode Method Title', 'woocommerce'),

						'type' 			=> 'text',

						'description' 	=> __('This controls the title which the user sees during checkout.', 'woocommerce'),

						'default'		=> __('Postcode Shipping(postcode)', $this->id),

					),

					'per_postcode_count' => array(

						'title' 		=> __('Number of Postcodes/Zip rules', $this->id),

						'type' 			=> 'number',

						'description'	=> __('How many different "per postcode" rates do you want to set?', $this->id).' '.__('Please save the options after changing this value.', $this->id),

						'default'		=> 1,

					),

				);

				$this->form_fields=$fields;

				

			}



			/* Per Country form fields */

			function init_shipping_fields_per_country() {

				global $woocommerce;

				$this->form_fields['per_country']=array(

					'title'         => __('Per Country Rates :::::::::::', $this->id),

					'type'          => 'title',

					/* 'description'   => __('Set how many "per country" fees as you want.', $this->id), */

				);

				$count=$this->settings['per_country_count'];

				for($counter = 1; $count >= $counter; $counter++) {

					$this->form_fields['per_country_'.$counter.'_country']=array(

						'title'		=> sprintf(__( 'Country #%s', $this->id), $counter),

						'type'		=> 'multiselect',

						'description'	=> __('Choose one or more countries for this rule.', $this->id),

						'class'		=> 'chosen_select',

						'css'		=> 'width: 450px;',

						'default'	=> '',

						'options'	=> $woocommerce->countries->countries

					);

					$this->form_fields['per_country_'.$counter.'_fee']=array(

						'title' 		=> sprintf(__( 'Delivery Fee #%s', 'woocommerce'), $counter).' ('.get_woocommerce_currency().')',

						'type' 			=> 'text',

						'description'	=> __('Set your quantity based shipping fee with semicolon (;) separated values for the countries specified above. Example:Quantity|Price;Quantity|Price. Example: 1|100;2|500.00 OR you can enter single price for all quantities Example: 100.00', $this->id),

						'default'		=> '',

						'placeholder'	=>	'1|10.00',

					);

				}

			}



			/* per State  form fields*/			

			function init_shipping_fields_per_state() {

				global $woocommerce;

				
				$this->form_fields['per_state']=array(

					'title'         => __('Per State Rates :::::::::::', $this->id),

					'type'          => 'title',

					/* 'description'   => __('Set how many "per state" fees as you want.', $this->id), */

				);

				

				$number = $this->state_group_no;



				$base_country = $woocommerce->countries->get_base_country();

				

				$count=$this->settings['per_state_count'];

				

				

				for($counter = 1; $count >= $counter; $counter++) {

					$this->form_fields['per_state_'.$counter.'_state']=array(

						'title'		=> sprintf(__( 'State #%s', $this->id), $counter),

						'type'		=> 'multiselect',

						'description'	=> __('Choose one or more State for this rule.', $this->id),

						'class'		=> 'chosen_select',

						'css'		=> 'width: 450px;',

						'default'	=> '',

						'options'	=> $woocommerce->countries->get_states( $base_country )

					);

					$this->form_fields['per_state_'.$counter.'_fee']=array(

						'title' 		=> sprintf(__( 'Delivery Fee #%s', 'woocommerce'), $counter).' ('.get_woocommerce_currency().')',

						'type' 			=> 'text',

						'description'	=> __('Set your quantity based shipping fee with semicolon (;) separated values for the state specified above. Example:Quantity|Price;Quantity|Price. Example: 1|100;2|500.00 OR you can enter single price for all quantities Example: 100.00', $this->id),

						'default'		=> '',

						'placeholder'	=>	'1|10.00',

					);

				}

			

			}

			

			

			/* per PostNumber form fields*/

			function init_shipping_fields_per_postcode() {

				global $woocommerce;
				
				$this->form_fields['per_postcode']=array(

					'title'         => __('Per Postcode/Zip Rates :::::::::::', $this->id),

					'type'          => 'title',

					/* 'description'   => __('Set how many "per postnumber" fees as you want.', $this->id), */

				);

								
				$count=$this->settings['per_postcode_count'];				

				
				for($counter = 1; $count >= $counter; $counter++) {

					$this->form_fields['per_postcode_'.$counter.'_postcode']=array(

						'title'		=> sprintf(__( 'Postcode/Zip #%s', $this->id), $counter),

						'type'		=> 'textarea',

						'description'	=> __('Choose one or more Postcodes for this rule. Separate codes with a comma and you can add postcode between 2 zones Example: 12345-12350,123456', $this->id),

						'default'	=> '',

						'placeholder'	=>	'12345-12350,123456 ect',

					);

					$this->form_fields['per_postcode_'.$counter.'_fee']=array(

						'title' 		=> sprintf(__( 'Delivery Fee #%s', 'woocommerce'), $counter).' ('.get_woocommerce_currency().')',

						'type' 			=> 'text',

						'description'	=> __('Set your quantity based shipping fee with semicolon (;) separated values for all postcodes specified above. Example:Quantity|Price;Quantity|Price. Example: 1|100;2|500.00 OR you can enter single price for all quantities Example: 100.00', $this->id),

						'default'		=> '',

						'placeholder'	=>	'1|10.00',

					);

					

				}

			
			}
			
			/***
			 * Find Postcode Function
			 * Usage: getPostcode(User_Enterd_Postcode, '1000-2000') 			 
			*/
			public function getPostcode($Usercode, $mpostcode){
				
				$mpostcode = explode("-",$mpostcode);
				
				$fCode= $mpostcode[0];
				$lCode = $mpostcode[1];
				for($fc=$fCode; $fc<=$lCode; $fc++){
					if($Usercode == $fc){
						$postCode=$fc;
						break;
					}
					else{
						$postCode='';	
					}
				}
				
				return $postCode;
			}
			
			/***
			 * Method Title: calPriceByQuantity
			 * Description : Calulate shipping Price By Quantity
			 * Syntax : calPriceByQuantity('cart_qty','qty|price,qty|price,qty|price')
			 * Usage: calPriceByQuantity('2', '1|100,2|200,3|300')
			 * Result: 200
			*/
			function calPriceByQuantity($Qty,$Price){
					
					$shipPrice ='';
					
					//Explode Price Classes
					$priceValue = explode(";",$Price);
					
					//Count of Price Classes
					$countPriceValue = count($priceValue);
					
					//Get Last Price Class
					$lastPriceClass = $priceValue[$countPriceValue-1];
					
					for($v=0; $v<$countPriceValue; $v++){
						
						$priceWithQty = $priceValue[$v];
						
						$finalPrice=$this->getQtyPrice($Qty, $priceWithQty);
						
						if($finalPrice != ''){
							
							$shipPrice =$finalPrice;
							
							break;
						}
									
					}
					
					//If Quantity exceeded. Last class price Will Apply  
					if($lastPriceClass !='' && $shipPrice==''){
									
						//Explode Last Price Class
						$otherPrice = explode("|",$lastPriceClass);
						
						$shipPrice=$otherPrice[1];	
									
					}
					
					return $shipPrice;
			}
			
			/***
			 * Method Title: getQtyPrice
			 * Description : Returns shipping Price By Quantity. Here You calciulate price only for single quantity. 
			 * Syntax : getQtyPrice('cart_qty','qty|price')
			 * Usage: getQtyPrice('1', '1|100')
			 * Result: 100
			*/
			 function getQtyPrice($Qty, $priceWithQty){
							
					$qtyPrice = explode("|",$priceWithQty);
					
					$qQuantity = $qtyPrice[0];
					$qPrice= '';
					
						if($Qty >0 && $Qty <= $qQuantity){
							
							//Return Price
							$qPrice= $qtyPrice[1];
							
						}		
					
					return $qPrice;
						
			 }

			
				
			/* Calculate the rate */  

			public function calculate_shipping($package) {

				// This is where you'll add your rates

				global $woocommerce;

				$label='';
				
				/** Method Title For Wordwide Shipping*/			
				$commonLabel=$this->settings['title']; 
				

				if(trim($package['destination']['postcode'])!='' || trim($package['destination']['state'])!='' || trim($package['destination']['country'])!='' ) {
					
					//Assign Default Flate Rate as False
					$final_rate=false;
					
					//Get Cart Quantity 
					$cartQuantity = $woocommerce->cart->cart_contents_count;
					
					
					//PostNumber
					$pcode =$package['destination']['postcode'];
					if($pcode != ''){
							
						/** Method Title For Postcode Shipping*/			
						$postcodeLabel=$this->settings['postcode_title']; 
					
						$postcount=$this->settings['per_postcode_count'];
						
						if ($final_rate===false) {
							
							for($i=1; $i<=$postcount; $i++){							
								
								$shipcode =$this->settings['per_postcode_'.$i.'_postcode'];
								
								$pcodes = explode(",",$shipcode);
								
								$p=0;
								
								foreach($pcodes as $pc) {									
									
									$singlePostcode = $pcodes[$p]; //Postcode
									
									$isMultiPostcodes = strpos($singlePostcode, "-");
									
									//IF Bulk Of Postcodes Separated With "-"
									if ($isMultiPostcodes === false) {
										
										$postCode=$singlePostcode; //Postcode
										if($pcode == $postCode){
																					
											//Is Qantity Based Price shipping
											$isMultiPrices = strpos($this->settings['per_postcode_'.$i.'_fee'], "|");
											
											if ($isMultiPrices === false) {
												
												$final_rate=floatval($this->settings['per_postcode_'.$i.'_fee']);
		
												$label=$label=__($postcodeLabel, $this->id);
			
												break;
											}else{
												
												//Get Price By Quantity
												$final_rate = $this->calPriceByQuantity($cartQuantity,$this->settings['per_postcode_'.$i.'_fee']);
													
												$label=$label=__($postcodeLabel, $this->id);
												
												break;
											}
																											
										}
										
									}else{
										
										$getPostcode=$this->getPostcode($pcode,$singlePostcode);
											
											if($pcode == $getPostcode){
												
												//Is Qantity Based Price shipping
												$isMultiPrices = strpos($this->settings['per_postcode_'.$i.'_fee'], "|");
												
												if ($isMultiPrices === false) {
													
													$final_rate=floatval($this->settings['per_postcode_'.$i.'_fee']);
			
													$label=$label=__($postcodeLabel, $this->id);
				
													break;
												}else{
													
													//Get Price By Quantity
													$final_rate = $this->calPriceByQuantity($cartQuantity,$this->settings['per_postcode_'.$i.'_fee']);
														
													$label=$label=__($postcodeLabel, $this->id);
													
													break;
												}
											}
												 
											
										
									}
									
									$p++;
								}
							}
						}
					}
					
					
					//State

					if ($final_rate===false) {
						
						/** Method Title For State Shipping*/			
						$stateLabel=$this->settings['state_title']; 
						
						$count=$this->settings['per_state_count'];

						for($i=1; $i<=$count; $i++){

							if (is_array($this->settings['per_state_'.$i.'_state'])) {

								if (in_array(trim($package['destination']['state']), $this->settings['per_state_'.$i.'_state'])) {

																		
									//Is Qantity Based Price shipping
									$isMultiPrices = strpos($this->settings['per_state_'.$i.'_fee'], "|");
									
									if ($isMultiPrices === false) {
										
										$final_rate=floatval($this->settings['per_state_'.$i.'_fee']);

										$label=$label=__($stateLabel, $this->id);
	
										break;
									}else{
										
										//Get Price By Quantity
										$final_rate = $this->calPriceByQuantity($cartQuantity,$this->settings['per_state_'.$i.'_fee']);
											
										$label=$label=__($stateLabel, $this->id);
										
										break;
									}

								}

							}

						}

					}


					//Country
					if ($final_rate===false) {
						
						/** Method Title For Country Shipping*/			
						$countryLabel=$this->settings['country_title']; 
						
						$count=$this->settings['per_country_count'];
	
						for($i=1; $i<=$count; $i++){
	
							if (is_array($this->settings['per_country_'.$i.'_country'])) {
	
								if (in_array(trim($package['destination']['country']), $this->settings['per_country_'.$i.'_country'])) {
									
									//Is Qantity Based Price shipping
									$isMultiPrices = strpos($this->settings['per_country_'.$i.'_fee'], "|");
									
									if ($isMultiPrices === false) {
										
										$final_rate=floatval($this->settings['per_country_'.$i.'_fee']);
	
										$label=$label=__($countryLabel, $this->id);
	
										break;
									}else{
										
										//Get Price By Quantity
										$final_rate = $this->calPriceByQuantity($cartQuantity,$this->settings['per_country_'.$i.'_fee']);
											
										$label=$label=__($countryLabel, $this->id);
										
										break;
									}
									
								}
	
							}
	
						}
					}
					
					
					//Rest of the World						
					if ($final_rate===false) {
						
						//IF Rest Of World Enabled
						$enableRestOfWorld=$this->settings['enable_restofworld'];
						if($enableRestOfWorld=='yes'){
							$final_rate=floatval($this->settings['fee_world']);
							$label=__($commonLabel, $this->id);
						}else{
							//Hide Shipping method
							return false;	
						}
					}
					

				} else {

					$final_rate=0; //No country?

				}

				$rate = array(

					'id'       => $this->id,

					'label'    => (trim($label)!='' ? $label : $this->title),

					'cost'     => $final_rate,

					'calc_tax' => 'per_order'

				);

				// Register the rate

				$this->add_rate($rate);

			}



		}

	}



	}

	add_action( 'woocommerce_shipping_init', 'woocommerce_flatrate_perpostcode_init' );



	/* Add to WooCommerce */

	function woocommerce_flatrate_perpostcode_add( $methods ) {

		$methods[] = 'WC_Flat_Rate_Per_Country_State_Postcode'; 

		return $methods;

	}

	add_filter( 'woocommerce_shipping_methods', 'woocommerce_flatrate_perpostcode_add' );



}