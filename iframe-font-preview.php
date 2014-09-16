<?php
/*
 * Create a preview of a font
 */

if ( empty( $_GET ) ) {
	return;
}

// Sanitize the inputs
foreach ( $_GET as $key => $value ) {
	$_GET[ $key ] = htmlspecialchars( $value );
}

// @see	http://bavotasan.com/2011/convert-hex-color-to-rgb-using-php/
function hex2rgb($hex) {
   $hex = str_replace("#", "", $hex);

   if(strlen($hex) == 3) {
      $r = hexdec(substr($hex,0,1).substr($hex,0,1));
      $g = hexdec(substr($hex,1,1).substr($hex,1,1));
      $b = hexdec(substr($hex,2,1).substr($hex,2,1));
   } else {
      $r = hexdec(substr($hex,0,2));
      $g = hexdec(substr($hex,2,2));
      $b = hexdec(substr($hex,4,2));
   }
   $rgb = array($r, $g, $b);
   //return implode(",", $rgb); // returns the rgb values separated by commas
   return $rgb; // returns an array with the rgb values
}

$textShadow = '';
if ( $_GET['text-shadow-location'] != 'none' ) {
	if ( stripos( $_GET['text-shadow-location'], 'left' ) !== false ) {
		$textShadow .= '-' . $_GET['text-shadow-distance'];
	} else if ( stripos( $_GET['text-shadow-location'], 'right' ) !== false ) {
		$textShadow .= $_GET['text-shadow-distance'];
	} else {
		$textShadow .= '0';
	}
	$textShadow .= ' ';
	if ( stripos( $_GET['text-shadow-location'], 'top' ) !== false ) {
		$textShadow .= '-' . $_GET['text-shadow-distance'];
	} else if ( stripos( $_GET['text-shadow-location'], 'bottom' ) !== false ) {
		$textShadow .= $_GET['text-shadow-distance'];
	} else {
		$textShadow .= '0';
	}
	$textShadow .= ' ';
	$textShadow .= $_GET['text-shadow-blur'];
	$textShadow .= ' ';

	$rgb = hex2rgb( $_GET['text-shadow-color'] );
	$rgb[] = $_GET['text-shadow-opacity'];

	$textShadow .= 'rgba(' . implode( ',', $rgb ) . ')';
} else {
	$textShadow .= $_GET['text-shadow-location'];
}

?>
<html>
	<head>
		<?php
		if ( $_GET['font-type'] == 'google' ) {
			$weight = $_GET['font-weight'];
			if ( $weight == 'normal' ) {
				$weight = array( '400' );
			} else if ( $weight == 'bold' ) {
				$weight = array( '500' );//, '700' );
			} else if ( $weight == 'bolder' ) {
				$weight = array( '800' );//, '900' );
			} else if ( $weight == 'lighter' ) {
				$weight = array( '100' );//, '200', '300' );
			} else {
				$weight = array( $weight );
			}
			if ( $_GET['font-style'] == 'italic' ) {
				foreach ( $weight as $key => $value ) {
					$weight[$key] = $value . 'italic';
				}
			}

			printf( "<link rel='stylesheet' href='http://fonts.googleapis.com/css?family=%s:400,%s&subset=latin,cyrillic-ext,greek-ext,greek,latin-ext,vietnamese,cyrillic' type='text/css' media='all' />",
				str_replace( ' ', '+', str_replace( '%20', '+', $_GET['font-family'] ) ),
				implode( ',', $weight )
			);

			$_GET['font-family'] = '"' . $_GET['font-family'] . '"';
		} else {
			$_GET['font-family'] = stripslashes( $_GET['font-family'] );
		}
		?>
		<style>
			p {
				margin-left: 50px;
				margin-right: 20px;
				font-family: <?php echo $_GET['font-family'] ?>;
				color: <?php echo $_GET['color'] ?>;
				font-size: <?php echo $_GET['font-size'] ?>;
				font-weight: <?php echo $_GET['font-weight'] ?>;
				font-style: <?php echo $_GET['font-style'] ?>;
				line-height: <?php echo $_GET['line-height'] ?>;
				letter-spacing: <?php echo $_GET['letter-spacing'] ?>;
				text-transform: <?php echo $_GET['text-transform'] ?>;
				font-variant: <?php echo $_GET['font-variant'] ?>;
				text-shadow: <?php echo $textShadow ?>;
			}
			body {
				margin: 20px 0;
				background: #fff;
				-webkit-touch-callout: none;
				-webkit-user-select: none;
				-khtml-user-select: none;
				-moz-user-select: none;
				-ms-user-select: none;
				user-select: none;
			}
			body.dark {
				background: #333;
			}
		</style>
		<script>
			function toggleClass(element, className){
			    if (!element || !className){
			        return;
			    }

			    var classString = element.className, nameIndex = classString.indexOf(className);
			    if (nameIndex == -1) {
			        classString += ' ' + className;
			    }
			    else {
			        classString = classString.substr(0, nameIndex) + classString.substr(nameIndex+className.length);
			    }
			    element.className = classString;
			}
		</script>
	</head>
	<body class='<?php echo $_GET['dark'] ?>'>
		<?php
		if ( empty( $_GET['text'] ) ):
			?>
			<p>Grumpy wizards make toxic brew for the evil Queen and Jack</p>
			<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nullam at dolor non purus adipiscing rhoncus. Nullam vitae turpis pharetra odio feugiat gravida sed ac velit. Nullam luctus ultrices suscipit. Fusce condimentum laoreet cursus. Suspendisse sed accumsan tortor. Quisque pharetra pulvinar ante, feugiat varius nibh sodales nec. Fusce vel mattis lectus. Vivamus magna felis, pharetra in lacinia sed, condimentum quis nisi. Ut at rutrum urna. Vivamus convallis posuere metus vel ullamcorper.</p>
			<?php
		else:
			echo "<p>" . str_replace( "\n", "</p><p>", $_GET['text'] ) . "</p>";
		endif;
		?>
	</body>
</html>
