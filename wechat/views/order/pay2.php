
<input type="button" value="pay" onclick="onBridgeReady();">
<script type="text/javascript" charset="utf-8">
function onBridgeReady(){
  WeixinJSBridge.invoke(
    'getBrandWCPayRequest', <?= $json ?>,
    function(res){
      if(res.err_msg == "get_brand_wcpay_request:ok" ){
        // 使用以上方式判断前端返回,微信团队郑重提示：
        //res.err_msg将在用户支付成功后返回ok，但并不保证它绝对可靠。
      }else {
        alert(JSON.stringify(res));
      }
    });
}
if (typeof WeixinJSBridge == "undefined"){
  if( document.addEventListener ){
    document.addEventListener('WeixinJSBridgeReady', onBridgeReady, false);
  }else if (document.attachEvent){
    document.attachEvent('WeixinJSBridgeReady', onBridgeReady);
    document.attachEvent('onWeixinJSBridgeReady', onBridgeReady);
  }
}else{
  alert('WeixinJSBridge:undefined');
}
</script>