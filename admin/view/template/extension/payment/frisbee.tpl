<?php echo $header; ?><?php echo $column_left; ?>
<div id="content">

    <div class="page-header">
        <div class="container-fluid">
            <div class="pull-right">
                <button type="submit" form="form" data-toggle="tooltip" title="<?php echo $button_save; ?>" class="btn btn-primary"><i class="fa fa-save"></i></button>
                <a href="<?php echo $cancel; ?>" data-toggle="tooltip" title="<?php echo $button_cancel; ?>" class="btn btn-default"><i class="fa fa-reply"></i></a></div>
            <h1><img src="view/image/payment/frisbee.png" style="height:25px; margin-top:-5px;" />   <?php echo $heading_title; ?></h1>
            <ul class="breadcrumb">
                <?php foreach ($breadcrumbs as $breadcrumb) { ?>
                <li><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a></li>
                <?php } ?>
            </ul>
        </div>
    </div>
    <div class="container-fluid">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title"><i class="fa fa-pencil"></i> <?php echo $text_edit; ?></h3>
            </div>
            <div class="panel-body">
                <form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form" class="form-horizontal">

                    <div class="form-group required">
                        <label class="col-sm-2 control-label"><?php echo $entry_merchant; ?></label>
                        <div class="col-sm-10">
                            <input type="text" name="frisbee_merchant" value="<?php echo $frisbee_merchant; ?>" class="form-control" />
                            <?php if ($error_merchant) { ?>
                            <span class="error"><?php echo $error_merchant; ?></span>
                            <?php } ?>
                        </div>
                    </div>

                    <div class="form-group required">
                        <label class="col-sm-2 control-label"><?php echo $entry_secretkey; ?></label>
                        <div class="col-sm-10">
                            <input type="text" name="frisbee_secretkey" value="<?php echo $frisbee_secretkey; ?>" class="form-control" />
                            <?php if ($error_secretkey) { ?>
                            <span class="error"><?php echo $error_secretkey; ?></span>
                            <?php } ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-2 control-label" ><?php echo $entry_currency; ?></label>
                        <div class="col-sm-10">
                            <select name="frisbee_currency"  class="form-control">
                                <?php foreach ($frisbee_currencies as $currency) { ?>

                                <?php if ($currency == $frisbee_currency) { ?>
                                <option value="<?php echo $currency; ?>" selected="selected"><?php echo $currency; ?></option>
                                <?php } else { ?>
                                <option value="<?php echo $currency; ?>"><?php echo $currency; ?></option>
                                <?php } ?>

                                <?php } ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-2 control-label" for="input-order-status"><?php echo $entry_order_status; ?></label>
                        <div class="col-sm-10">
                            <select name="frisbee_order_status_id" class="form-control">
                                <?php
                foreach ($order_statuses as $order_status) { 

                $st = ($order_status['order_status_id'] == $frisbee_order_status_id) ? ' selected="selected" ' : ""; 
                ?>
                                <option value="<?php echo $order_status['order_status_id']; ?>" <?= $st ?> ><?php echo $order_status['name']; ?></option>
                                <?php } ?>
                            </select> </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-2 control-label" for="input-order-status"><?php echo $entry_order_status_cancelled; ?></label>
                        <div class="col-sm-10">
                            <select name="frisbee_order_cancelled_status_id" class="form-control">
                                <?php
                foreach ($order_statuses as $order_status) { 

                $st = ($order_status['order_status_id'] == $frisbee_order_cancelled_status_id) ? ' selected="selected" ' : ""; 
                ?>
                                <option value="<?php echo $order_status['order_status_id']; ?>" <?= $st ?> ><?php echo $order_status['name']; ?></option>
                                <?php } ?>
                            </select> </div>
                    </div>


                    <div class="form-group">
                        <label class="col-sm-2 control-label" for="input-order-status"><?php echo $entry_order_process_status; ?></label>
                        <div class="col-sm-10">
                            <select name="frisbee_order_process_status_id" class="form-control">
                                <?php
                foreach ($order_statuses as $order_status) {

                $st = ($order_status['order_status_id'] == $frisbee_order_process_status_id) ? ' selected="selected" ' : "";
                ?>
                                <option value="<?php echo $order_status['order_status_id']; ?>" <?= $st ?> ><?php echo $order_status['name']; ?></option>
                                <?php } ?>
                            </select></div>
                    </div>


                    <div class="form-group">
                        <label class="col-sm-2 control-label" for="input-status"><?php echo $entry_status; ?></label>
                        <div class="col-sm-10">
                            <select name="frisbee_status" class="form-control">
                                <? $st0 = $st1 = "";
                 if ( $frisbee_status == 0 ) $st0 = 'selected="selected"';
                  else $st1 = 'selected="selected"';
                ?>

                                <option value="1" <?= $st1 ?> ><?php echo $text_enabled; ?></option>
                                <option value="0" <?= $st0 ?> ><?php echo $text_disabled; ?></option>

                            </select>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php echo $footer; ?>
