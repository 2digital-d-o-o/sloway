<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>Naročilo št.: <?php echo $this->order->id . date('-Y',$this->order->date) ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=windows-1250">
<link type="text/css" rel="stylesheet"  href="/media/admin/css/admin.css" />
<script language="JavaScript" type="text/JavaScript">
<!--

function MM_preloadImages() { //v3.0
  var d=document; if(d.images){ if(!d.MM_p) d.MM_p=new Array();
    var i,j=d.MM_p.length,a=MM_preloadImages.arguments; for(i=0; i<a.length; i++)
    if (a[i].indexOf("#")!=0){ d.MM_p[j]=new Image; d.MM_p[j++].src=a[i];}}
}
//-->
</script>
<script language="JavaScript">
<!--
function printArticle() {
    if (window.print) {
        setTimeout('window.print();',200);
    }
    else if (agt.indexOf("mac") != -1) {
        alert("Press 'Cmd+p' on your keyboard to print article.");
    }
    else {
        alert("Press 'Ctrl+p' on your keyboard to print article.")
    }
}

function hidePrint() {
     document.all.print.src="material/tiskaj_dopis0.gif"  
}

function showPrint() {
     document.all.print.src="material/tiskaj_dopis0.gif"
}

function MM_swapImgRestore() { //v3.0
  var i,x,a=document.MM_sr; for(i=0;a&&i<a.length&&(x=a[i])&&x.oSrc;i++) x.src=x.oSrc;
}

function MM_findObj(n, d) { //v4.01
  var p,i,x;  if(!d) d=document; if((p=n.indexOf("?"))>0&&parent.frames.length) {
    d=parent.frames[n.substring(p+1)].document; n=n.substring(0,p);}
  if(!(x=d[n])&&d.all) x=d.all[n]; for (i=0;!x&&i<d.forms.length;i++) x=d.forms[i][n];
  for(i=0;!x&&d.layers&&i<d.layers.length;i++) x=MM_findObj(n,d.layers[i].document);
  if(!x && d.getElementById) x=d.getElementById(n); return x;
}

function MM_swapImage() { //v3.0
  var i,j=0,x,a=MM_swapImage.arguments; document.MM_sr=new Array; for(i=0;i<(a.length-2);i+=3)
   if ((x=MM_findObj(a[i]))!=null){document.MM_sr[j++]=x; if(!x.oSrc) x.oSrc=x.src; x.src=a[i+2];}
}
//-->
</script>
<style type="text/css">
<!--
.style1 {
    font-size: 11px;
    font-family: Verdana, Arial, Helvetica, sans-serif;
}
-->
</style>
</head>

<body style="background-color: white; width: 644px" leftmargin="0" topmargin="0" marginwidth="0" marginheight="0" onLoad="MM_preloadImages('/media/img/tiskaj1.gif')">

<?php echo new View('AdminOrders/Receipt') ?>

<table width="644" height="495" border="0" cellpadding="0" cellspacing="0">
  <tr>
    <td height="32" bgcolor="#FFFFFF"><table width="644" height="97" border="0" cellpadding="0" cellspacing="0">
      <tr>
        <td width="568" height="65"><img src="/media/img/logo_biba.png" width="179" height="106"></td>
        <td width="76" height="31" valign="bottom"><div align="center"></div></td>
      </tr>
      <tr>
        <td height="15"><span class="style1"><STRONG>Biba tade d.o.o., 
        </STRONG> Zaloška 155, 1000 Ljubljana, Tel: 04/515-50-09</span></td>
        <td height="32" align="center" valign="middle"><a href="#" onMouseOut="MM_swapImgRestore()" onMouseOver="MM_swapImage('Image3','','/media/img/tiskaj1.gif',1)"><img src="/media/img/tiskaj.gif" name="Image3" width="64" height="22" border="0"></a></td>
      </tr>
    </table></td>
  </tr>
  <tr>
    <td height="2" bgcolor="#FFFFFF"><img src="/media/img/crta.gif" width="644" height="2"></td>
  </tr>
</table>
</body>
</html>



