<?php
// TODO: Remove in version 1.5

/*
 * Create a preview of a font
 */
// var_dump($_GET['f']);
if (!isset($_GET['f'])) die('missing parameters');
if (!isset($_GET['t']) || !trim($_GET['t'])) {
	// $_GET['t'] = "AaBbCcDdEeFfGgHhIiJjKkLl1234567890[]{};:'\",.?!@#\$%^&*ÀËÔÙáéóûæ€£¥©®™";
		$_GET['t'] = "Grumpy wizards make toxic brew for the evil Queen and Jack";
}
// $_GET['t'] = urldecode($_GET['t']);

$f = str_replace( ' ', '+', $_GET['f'] );

$fontFamily = $f;
$variant = '';
if ( strpos( $f, ':' ) ) {
	list( $fontFamily, $variant ) = explode( ':', $f );
}
$fontFamily = str_replace( '+', ' ', $fontFamily );

$fontStyle = '';
$fontWeight = $variant;
if ( strpos( $variant, 'italic' ) !== false ) {
	$fontStyle = "font-style: italic;";
	$fontWeight = str_replace( 'italic', '', $variant );
}
if ( $fontWeight ) {
	$fontWeight = "font-weight: $fontWeight;";
}

// $_GET['t'] = preg_replace("<br>", "", $_GET['t']);

?>
<html>
	<head>
		<link rel='stylesheet' href="http://fonts.googleapis.com/css?family=<?php echo $f ?>&subset=latin,cyrillic-ext,greek-ext,greek,latin-ext,vietnamese,cyrillic" type='text/css' media='all' />
		<style>
			p, h1, h2, h3, h4, h5, h6 {
				font-family: '<?php echo $fontFamily ?>';
				<?php echo $fontStyle ?>
				<?php echo $fontWeight ?>
				font-size: 33px;
				line-height: 33px;
			}
		</style>
	</head>
	<body>
		<h3 style="line-height: 50px;"><?php echo $_GET['t'] ?></h3>
	</body>
</html>