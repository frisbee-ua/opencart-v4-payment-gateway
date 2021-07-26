<?php if ($frisbee['result'] == false) {echo $frisbee['message']; die;}?>
<div style="display: none" id="checkout">
    <div id="checkout_wrapper" ></div>
</div>
<script type="text/javascript">
var checkoutStyles = {
<?php echo $styles; ?>
	};

    function checkoutInit(url) {
        window.location = url;
    };

</script>
<div class="buttons">
        <div class="pull-right">
            <a onclick="checkoutInit('<?php echo $frisbee['url'] ?>');" class="btn btn-primary"><?php echo $button_confirm; ?></a>
        </div>
</div>
