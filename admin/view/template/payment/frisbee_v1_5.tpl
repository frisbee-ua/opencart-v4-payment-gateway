<?php echo $header; ?>
<div id="content">
	<div class="breadcrumb">
		<?php foreach ($breadcrumbs as $breadcrumb) { ?>
		<?php echo $breadcrumb['separator']; ?><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a>
		<?php } ?>
	</div>
	<?php if ($error_warning) { ?>
	<div class="warning"><?php echo $error_warning; ?></div>
	<?php } ?>
	<div class="box">
		<div class="heading">
			<h1><img src="view/image/payment/frisbee.png" style="height:25px; margin-top:-5px;" /> <?php echo $heading_title; ?></h1>
			<div class="buttons"><a onclick="$('#form').submit();" class="button"><?php echo $button_save; ?></a><a onclick="location = '<?php echo $cancel; ?>';" class="button"><?php echo $button_cancel; ?></a></div>
		</div>
		<div class="content">
			<form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form">
				<table class="form">
					<tr>
						<td><span class="required">*</span> <?php echo $entry_merchant; ?></td>
						<td><input type="text" name="frisbee_merchant" value="<?php echo $frisbee_merchant; ?>" />
							<?php if ($error_merchant) { ?>
							<span class="error"><?php echo $error_merchant; ?></span>
							<?php } ?></td>
					</tr>
					<tr>
						<td><span class="required">*</span> <?php echo $entry_secretkey; ?></td>
						<td><input type="text" name="frisbee_secretkey" value="<?php echo $frisbee_secretkey; ?>" />
							<?php if ($error_secretkey) { ?>
							<span class="error"><?php echo $error_secretkey; ?></span>
							<?php } ?></td>
					</tr>

					<tr>
						<td><?php echo $entry_currency; ?></td>
						<td><select name="frisbee_currency">
								<?php foreach ($frisbee_currencyc as $frisbee_currenc) { ?>

								<?php if ($frisbee_currenc == $frisbee_currency) { ?>
								<option value="<?php echo $frisbee_currenc; ?>" selected="selected"><?php echo $frisbee_currenc; ?></option>
								<?php } else { ?>
								<option value="<?php echo $frisbee_currenc; ?>"><?php echo $frisbee_currenc; ?></option>
								<?php } ?>

								<?php } ?>
							</select></td>
					</tr>

					<tr>
						<td><?php echo $entry_language; ?></td>
						<td><input type="text" name="frisbee_language" value="<?php echo ($frisbee_language == "") ? "RU" : $frisbee_language; ?>" /></td>
					</tr>

					<tr>
						<td><?php echo $entry_order_status; ?></td>
						<td><select name="frisbee_order_status_id">
								<?php
                foreach ($order_statuses as $order_status) {

                $st = ($order_status['order_status_id'] == $frisbee_order_status_id) ? ' selected="selected" ' : "";
                ?>
								<option value="<?php echo $order_status['order_status_id']; ?>" <?= $st ?> ><?php echo $order_status['name']; ?></option>
								<?php } ?>
							</select></td>
					</tr>
					<tr>
						<td><?php echo $entry_order_process_status; ?></td>
						<td><select name="frisbee_order_process_status_id">
								<?php
                foreach ($order_statuses as $order_status) {

                $st = ($order_status['order_status_id'] == $frisbee_order_process_status_id) ? ' selected="selected" ' : "";
                ?>
								<option value="<?php echo $order_status['order_status_id']; ?>" <?= $st ?> ><?php echo $order_status['name']; ?></option>
								<?php } ?>
							</select></td>
					</tr>
					<tr>
						<td><?php echo $entry_order_status_cancelled; ?></td>
						<td><select name="frisbee_order_cancelled_status_id">
								<?php
                foreach ($order_statuses as $order_status) {

                $st = ($order_status['order_status_id'] == $frisbee_order_cancelled_status_id) ? ' selected="selected" ' : "";
                ?>
								<option value="<?php echo $order_status['order_status_id']; ?>" <?= $st ?> ><?php echo $order_status['name']; ?></option>
								<?php } ?>
							</select></td>
					</tr>
					<tr>
						<td><?php echo $entry_status; ?></td>
						<td><select name="frisbee_status">
								<?php $st0 = $st1 = "";
                 if ( $frisbee_status == 0 ) $st0 = 'selected="selected"';
                  else $st1 = 'selected="selected"';
                ?>

								<option value="1" <?= $st1 ?> ><?php echo $text_enabled; ?></option>
								<option value="0" <?= $st0 ?> ><?php echo $text_disabled; ?></option>

							</select></td>
					</tr>
					<tr>
						<td><?php echo $entry_sort_order; ?></td>
						<td><input type="text" name="frisbee_sort_order" value="<?php echo $frisbee_sort_order; ?>" size="1" /></td>
					</tr>
				</table>
			</form>
		</div>
	</div>
</div>
<?php echo $footer; ?>
