<?php
/**
* Remove some line breaks after 7to3 converter, because phpcbf will move starting and ending php tags to new lines.
* And this will create incorrect html attributes.
*/

/**
* Return all files in directory
*
* @param string $dir
*/
function dir_to_array( $dir ) {
	$result = [];
	$cdir = scandir( $dir );
	foreach ( $cdir as $key => $value ) {
		if ( ! in_array( $value,[ ".",".." ] ) ) {
			if ( is_dir( $dir . DIRECTORY_SEPARATOR . $value ) ) {
				$items = dir_to_array( $dir . DIRECTORY_SEPARATOR . $value );
				if ( ! empty( $items ) ) {
					$result = array_merge( $result, $items );
				}
			} else {
				if ( strrpos( $value, '.php' ) ) {
					$result[] = $dir . DIRECTORY_SEPARATOR . $value;
				}
			}
		}
	}
	return $result;
}

/**
* Update file
*
* @param string $file
*/
function update_file( $file ) {
	$data = file_get_contents( $file );
	$data2 = $data;

	/*
	<img src="<?php echo esc_attr( AHREFS_SEO_IMAGES_URL ); ?>open.png"
	srcset="<?php echo esc_attr( AHREFS_SEO_IMAGES_URL ); ?>open@2x.png 2x,
	<?php echo esc_attr( AHREFS_SEO_IMAGES_URL ); ?>open@3x.png 3x"
	class="icon">
	*/
	$data2 = preg_replace( "!srcset=\"<\?php\s+(.*?)\s+\?>(.*?) 2x,\s+<\?php\s+(.*?)\s\?>(.*?) 3x\"!ms", "srcset=\"<?php $1 ?>$2 2x, \n<?php $3 ?>$4 3x\"", $data2 );

	/*
	<img src="<?php
	echo esc_attr(AHREFS_SEO_IMAGES_URL);
	?>backlinks-demo.png" class="image-centered">
	*/
	$data2 = preg_replace( "!src=\"<\?php \n(.*?)\s\?>([0-9a-z\-_@]+).(png|jpg)\"!ms", 'src="<?php $1 ?>$2.$3"', $data2 );

	$data2 = preg_replace( "!=\"<\?php[ \n\r\s]*!m", '="<?php ', $data2 );
	$data2 = preg_replace( "!\n\s*\?>\s*\"!m", ' ?>"', $data2 );

	/*
	"// phpcs:ignore" comment must be appended to previous string
	*/
	$data2 = preg_replace( "![ \n\r\s]+// phpcs:ignore!m", ' // phpcs:ignore', $data2 );

	/*
	fix line breaks and tabs chars in sql create statements.
	*/
	$data2 = preg_replace_callback(
		'!("CREATE TABLE)(.*?)(")!ms',
		function( $matches ) {
			return str_replace( [ '\n', '\t' ], [ "\n", "\t" ], $matches[0] );
		},
		$data2
	);

	// comment on same line or on the next.
	$parts = explode( '/php5', $file, 2 );
	if ( 2 === count( $parts ) ) {
		$php7_file = $parts[0] . '/php7' . $parts[1];
		if ( file_exists( $php7_file ) ) {
			$content7 = file_get_contents( $php7_file );
			$content7 = str_replace( [ '      ', '     ', '    ', '   ', '  ', ' ' ], [' ', ' ', ' ', ' ', ' ', ' ' ], $content7 );
			$data2 = preg_replace_callback(
				'!([\n][^\n]*?)[ \n\r\s\t]+(//[^\n]+)!ms',
				function( $matches ) use ( $content7 ) {
					$string = ltrim( $matches[1] ) . ' ' . rtrim( $matches[2] );
					$string = str_replace( [ '      ', '     ', '    ', '   ', '  ', ' ' ], [' ', ' ', ' ', ' ', ' ', ' ' ], $string );
					$string = str_replace( '   ', ' ', $string );
					$string = str_replace( '  ', ' ', $string );
					// if was at the same line at original - remove "\n", otherwise leave untouched.
					$found = '' !== trim( $matches[1] ) && false !== strpos( $content7, $string );
					return $found ? rtrim( $matches[1] ) . ' ' . $matches[2] : $matches[0];
				},
				$data2
			);
		}
	}
	// remove parameter type \Throwable, as not defined in php5 workarounds.
	$data2 = str_replace( '\\Throwable $e', '$e', $data2 );

	if ( $data !== $data2 ) {
		file_put_contents( $file, $data2 );
		echo "Updated: $file\n";
	}

	if ( $data !== $data2 ) {
		file_put_contents( $file, $data2 );
		echo "Updated: $file\n";
	}
}

if ( 2 === $argc ) {
	$dir = $argv[1];
	$files = dir_to_array( $dir );

	foreach ( $files as $file ) {
		update_file( $file );
	}
}
