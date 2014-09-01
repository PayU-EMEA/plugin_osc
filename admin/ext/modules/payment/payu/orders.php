<script type="text/javascript">
$(function(){
	$('td.smallText:first').prepend("<span class=\"tdbLink\"><a id=\"tdb_refund\" href=\"<?php echo tep_href_link('ext/modules/payment/payu/refund.php', 'oID=' . $HTTP_GET_VARS['oID']); ?>\">Refund</a></span>");
	$("#tdb_refund").button({icons:{primary:"ui-icon-document"}}).addClass("ui-priority-secondary").parent().removeClass("tdbLink");	
});
</script>