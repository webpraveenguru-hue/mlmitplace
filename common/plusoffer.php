<?php
include_once('../common/init.loader.php');
?>

<div>
    You are currently using a <strong>Regular</strong> license. To take advantage of more features, please consider upgrading to a <strong>Extended</strong> license.
</div>

<div class="text-right mt-4">
    <a href="javascript:;" class="btn btn-secondary" data-dismiss="modal"><i class="fa fa-fw fa-times"></i> Cancel</a>
    <a href="javascript:;" onclick="location.href = '<?php echo myvalidate($ssysout('SSYS_URL') . '/docs/' . strtolower($ssysout('SSYS_NAME')) . '/index.php?todo=upgrade'); ?>'" class="btn btn-primary"><i class="fa fa-fw fa-rocket"></i> Upgrade Now</a>
</div>

