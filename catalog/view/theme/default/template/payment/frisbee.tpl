<?php if ($frisbee['result'] == false) {echo $frisbee['message']; die;}?>
<div style="display: none" id="checkout">
	<div id="checkout_wrapper" ></div>
</div>
<div class="buttons">
	<div class="pull-right right">
		<a href="<?php echo $frisbee['url'] ?>" class="btn btn-primary button"><?php echo $button_confirm; ?></a>
	</div>
</div>
