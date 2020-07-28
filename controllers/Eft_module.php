<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Eft_module extends App_Controller
{
	public function complete_purchase($invoice_id, $invoice_hash)
	{
	
			check_invoice_restrictions($invoice_id, $invoice_hash);

			$this->load->model('invoices_model');
			$invoice = $this->invoices_model->get($invoice_id);
			load_client_language($invoice->clientid);

			$amount = $invoice->total;
			$status = $this->input->get('status');
			$merchant_reference = $this->input->get('merchant_reference');
			$gateway_reference = $this->input->get('gateway_reference');
			
			
			if ($status=='success') {
				$transaction_id = $this->input->get('transaction_id');
				$merchant_reference = $this->input->get('merchant_reference');
				$gateway_reference = $this->input->get('gateway_reference');
				$organisation_id = $this->input->get('organisation_id');

				$success = $this->eft_gateway->addPayment(
				  [
						'amount'		=> $amount,
						'invoiceid'	 => $invoice->id,
						'paymentmethod' => $gateway_reference,
						'transactionid' => $transaction_id,
				  ]
				);

				set_alert($success ? 'success' : 'danger', _l($success ? 'online_payment_recorded_success' : 'online_payment_recorded_success_fail_database'));
			} else {
				set_alert('danger', $oResponse->getMessage());
			}
			$invoice_url = site_url('invoice/'.$invoice->id.'/'. $invoice->hash);
			header('Location: '.$invoice_url);
	}

	public function make_payment()
	{
		check_invoice_restrictions($this->input->get('invoiceid'), $this->input->get('hash'));
		$this->load->model('invoices_model');
		$invoice = $this->invoices_model->get($this->input->get('invoiceid'));
		load_client_language($invoice->clientid);
		$data['invoice']	  = $invoice;
		$data['total']		= $this->input->get('total');
		$data['logo'] = $this->eft_gateway->getLogo();
    $success_url = site_url('eft_module/complete_purchase/'.$data['invoice']->id.'/'. $data['invoice']->hash);
    $error_url = site_url('eft_module/make_payment/?invoiceid='.$data['invoice']->id.'&hash='. $data['invoice']->hash).'&total='.$data['total'];
    $cancel_url = site_url('eft_module/make_payment/?invoiceid='.$data['invoice']->id.'&hash='. $data['invoice']->hash).'&total='.$data['total'];
		$token = $this->eft_gateway->generate_token($data['total'], $success_url, $error_url, $cancel_url);
		$data['token'] = $token[0]->key;
		$data['merchant'] = $token[1];
		echo $this->get_view($data);
	}
	public function get_view($data = [])
	{ 
		?>
  <?php echo payment_gateway_head(_l('payment_for_invoice') . ' ' . format_invoice_number($data['invoice']->id)); ?>
  <head>
  	<link href="<?php echo module_dir_url('eft_module', 'assets/css/style.css'); ?>" rel="stylesheet">
  	<link rel="stylesheet" type="text/css" href="https://eft.ppay.io/css/eftx.css">
  </head>
  <body class="gateway-braintree">
	<div class="container">
	  <div class="col-md-8 col-md-offset-2 mtop30">
		<div class="mbot30 text-center">
		  <?php echo payment_gateway_logo(); ?>
		</div>
		<?php if($this->input->get('status')=='error'):?>
		<div class="row">
			<div class="alert alert-warning">
				<h4 class="alert-heading">Warning</h4>
				<p>Payment has been failed.</p>
			</div>
		</div>
		<?php endif;?>
		<div class="row">
		  <div class="panel_s">
			<div class="panel-body">
			 <h3 class="no-margin">
			  <b><?php echo _l('payment_for_invoice'); ?></b>
			  <a href="<?php echo site_url('invoice/' . $data['invoice']->id . '/' . $data['invoice']->hash); ?>">
				<b><?php echo format_invoice_number($data['invoice']->id); ?></b>
			  </a>
			</h3>
			<h4><?php echo _l('payment_total', app_format_money($data['total'], $data['invoice']->currency_name)); ?></h4>
			<hr />
			<form id="#payment-form" action="" style="margin-top: 50px">
			  <div class="text-center" id="payment-buttons">
				<button id="pay-btn" type="button" class="btn btn-primary" data-toggle="modal" data-target="#eftxModal">
				  Pay Now
				</button>

			</form>
		  </div>
		</div>
	  </div>
	</div>
	
  </div>
<div class="eft modal" id="eftxModal" data-backdrop="false" tabindex="-1" role="dialog" aria-labelledby="title">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
            	<div class="logo pull-left">
            		<img src="<?php echo $data['logo'];//echo module_dir_url('eft_module', 'assets/images/logo.png'); ?>" alt="Africa Chain" style="width: 150px;">
            	</div>
            	<div class="pull-right" style="margin-top: 10px;">
                <h4 class="modal-title" style="color: #666;">R <?php echo $data['total'];?></h4>
                <div style="color: #666;">Merchant: <?php echo $data['merchant'];?></div>
            	</div>
            </div>
            <div class="modal-body">
                <h4 id="eftx-subtitle"></h4>
                <div class="eftx-loader">
                  <div class="loader">
                      <div class="loader--dot"></div>
                      <div class="loader--dot"></div>
                      <div class="loader--dot"></div>
                      <div class="loader--dot"></div>
                      <div class="loader--dot"></div>
                      <div class="loader--dot"></div>
                      <div class="loader--text"></div>
                  </div>
                </div>
                <form id="eftx-form" autocomplete="false"></form>
                <div class="clearfix"></div>
            </div>
            <div class="modal-footer">
            	<div class="footer-security text-center">
								We take your privacy and security very seriously and implement proven safety measures to protect your transactions. This website is PCI compliant and follows stringent data security standards required by the South African Reserve Bank and All South African Banks.
							</div>
            	<div class="africalogo text-center">

            		<div class="inline">
	            		<p>
	            			<img class="tiny-img" src="<?php echo module_dir_url('eft_module', 'assets/images/ssl.png'); ?>">
	            			<img class="tiny-img" src="<?php echo module_dir_url('eft_module', 'assets/images/pci.png'); ?>">
	            			<img class="tiny-img" src="<?php echo module_dir_url('eft_module', 'assets/images/secpay.png'); ?>"></p>
	            		<p>Secure Payments Powered By</p>
	            	</div>
            		<img src="<?php echo module_dir_url('eft_module', 'assets/images/logo.png'); ?>" alt="Africa Chain" style="width: 100px;">
            	</div>
            	<div id="eftx-button-container"></div>
            </div>
        </div>
    </div>
</div>
	<script type="text/javascript" src="https://code.jquery.com/jquery-1.12.4.min.js"></script>
		<script type="text/javascript" src="https://eft.ppay.io/js/eft-secure.min.js"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
		<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
	<?php //echo payment_gateway_scripts(); ?>
	<script>
	  $(document).ready(function() {
      $("#pay-btn").click(function(){
        new Eftx({
            key: "<?php echo $data['token'];?>",
            //enum[bootstrap, none]
            cssFramework: "bootstrap",
            buttonContainerId: "eftx-button-container",
            //onCancelUrl: "{{yourCancelUrl}}",
            //onCompleteUrl: "{{yourCompleteUrl}}",
            backgroundEnabled: true,
            onLoadStart: function(){
                $(".eftx-loader").removeClass("hide"); $("#eftx-form").hide();
                $(".modal-footer button").hide();
                //Do other stuff here
            },
            onLoadStop: function(){
                $(".eftx-loader").addClass("d-none"); $("#eftx-form").show();
                $(".modal-footer button").show();
                $(".eftx-loader").addClass("hide");
                //if($("#eftx-subtitle").text()=='Pay Quickly and Securely'){
                	$("#eftx-subtitle").text('Pay Quickly and Securely by Selecting your Bank Below');
                //}
                //Do other stuff here
            }
        });
      });
     });
	</script>
</body>
	<?php echo payment_gateway_footer();
	}
}
