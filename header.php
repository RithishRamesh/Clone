<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<title><?php echo $installname; if (isset($pagetitle)) { echo " - $pagetitle";}?></title>
<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
<link rel="stylesheet" href="<?php echo $imasroot . "/imas.css";?>" type="text/css" />
<link rel="shortcut icon" href="/favicon.ico" />
<style type="text/css" media="print">
div.breadcrumb { display:none;}
img#headerlogo { display:none;}
</style>
<script type="text/javascript">
var imasroot = '<?php echo $imasroot; ?>';
</script>
<?php
if (!isset($sessiondata['mathdisp'])) {
	echo "<script src=\"$imasroot/javascript/mathgraphcheck.js\" type=\"text/javascript\"></script>\n";
}
if ($sessiondata['mathdisp']==1) {
	echo "<script src=\"$imasroot/javascript/ASCIIMathML.js\" type=\"text/javascript\"></script>\n";
	echo "<script type=\"text/javascript\">var usingASCIIMath = true;</script>";
} else if ($sessiondata['mathdisp']==2 && isset($useeditor) && $sessiondata['useed']==1) {
	echo '<script type="text/javascript">var AMTcgiloc = "'.$mathimgurl.'";</script>'; 
	echo "<script src=\"$imasroot/javascript/ASCIIMathTeXImg.js\" type=\"text/javascript\"></script>\n";
	echo "<script type=\"text/javascript\">var usingASCIIMath = false;</script>";
} else {
	echo "<script type=\"text/javascript\">var usingASCIIMath = false;</script>";
}
if ($sessiondata['graphdisp']==1) {
	echo "<script src=\"$imasroot/javascript/ASCIIsvg.js\" type=\"text/javascript\"></script>\n";
	echo "<script type=\"text/javascript\">var usingASCIISvg = true;</script>";
	echo "<script src=\"$imasroot/course/editor/plugins/AsciiSvg/ASCIIsvgAddon.js\" type=\"text/javascript\"></script>\n";
} else if (isset($sessiondata['graphdisp'])) {
	echo "<script src=\"$imasroot/javascript/mathjs.js\" type=\"text/javascript\"></script>\n";
	echo "<script type=\"text/javascript\">var usingASCIISvg = false;</script>";
}

$start_time = microtime(true); 
if (isset($placeinhead)) {
	echo $placeinhead;
}
if (isset($useeditor) && $sessiondata['useed']==1) {
echo <<<END
<script type="text/javascript">
  _editor_url = "$imasroot/course/editor";
  _imasroot = "$imasroot/";
  _editor_lang = "en";
</script>
<script type="text/javascript" src="$imasroot/course/editor/htmlarea.js"></script>
<script type="text/javascript">
END;
if (!isset($sessiondata['mathdisp']) || $sessiondata['mathdisp']==1 || $sessiondata['mathdisp']==2) {
 echo 'HTMLArea.loadPlugin("AsciiMath");';
} 
if (!isset($sessiondata['graphdisp']) || $sessiondata['graphdisp']==1) {
 echo 'HTMLArea.loadPlugin("AsciiSvg");';
 echo 'var svgimgbackup = false;';
} else if ($sessiondata['graphdisp']==2) {
 echo 'HTMLArea.loadPlugin("AsciiSvg");';
 echo 'var svgimgbackup = true;';
}
 echo 'var AScgiloc ="'.$imasroot.'/filter/graph/svgimg.php";'; 
echo <<<END
</script>

<script type="text/javascript">
END;
$editors = explode(",",$useeditor);
for ($i=1; $i<=count($editors); $i++) {
	echo "var editor$i = null;\n";
}
echo "function initEditor() {\n";
$i=0;
foreach ($editors as $editor) {
$i++;
echo "editor$i = new HTMLArea(\"$editor\");\n";
echo "editor$i.config.hideSomeButtons(\" popupeditor lefttoright righttoleft \");\n";
if (!isset($sessiondata['mathdisp']) || $sessiondata['mathdisp']==1 || $sessiondata['mathdisp']==2) {
	echo "editor$i.registerPlugin(AsciiMath);\n";
	//surrounds AsciiMath in red box while editting.  Change to your liking
	echo "editor$i.config.pageStyle = \"span.AMedit {border:solid 1px #ff0000}\";\n";
	echo "editor$i.config.toolbar[1].push(\"separator\",\"insertnewmath\",\"insertmath\",\"swapmathmode\");\n";
}
if (!isset($sessiondata['graphdisp']) || $sessiondata['graphdisp']==1 || $sessiondata['graphdisp']==2) {
	echo "editor$i.registerPlugin(AsciiSvg);\n";
	echo "editor$i.config.toolbar[1].push(\"separator\",\"insertsvg\");\n";
}
echo "editor$i.generate();\n";
}
echo <<<END
  return false;
};

</script>

</head>

<body>
END;
} else {
	echo "</head>\n";
	echo "<body>\n";
}

//load filter
$curdir = rtrim(dirname(__FILE__), '/\\');
require("$curdir/filter/filter.php");

//CUSTOMIZE:  put a small (max 120px wide) logo on upper right of course pages
if (!isset($nologo)) {
	//echo '<img id="headerlogo" style="position: absolute; right: 5px; top: 5px;" src="/img/state_logo.gif" alt="logo"/>';
	echo '<img id="headerlogo" style="position: absolute; right: 5px; top: 12px;" src="/img/wamaplogosmall.gif" alt="logo"/>';
}
?>

<div class=mainbody>
