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
