<?php

/**
 * Convert bytes to a human readable figure
 * @param unknown_type $size
 */
function convert_size_human($size,$round=true){
	$unit=array('','KB','MB','GB','TB','PB');
	$byte_size = $size/pow(1024,($i=floor(log($size,1024))));

	if(preg_match('/^[0-9]+$/', $byte_size)||$round){
		return $byte_size.' '.$unit[$i];
	}else{
		preg_match('/^[0-9]+\.[0-9]{2}/', $byte_size, $matches);
		return $matches[0].' '.$unit[$i];
	}
}

/**
 * Strips all from string including symbols and punctuation
 * @param $blurb
 */
function strip_all($blurb){

	$blurb = stripslashes(strip_tags($blurb));
	$blurb = preg_replace('/<[^>]*>/', '', $blurb);
	$blurb = trim(preg_replace('/(?:\s\s+|\n|\t)/', ' ', $blurb));
	$blurb = preg_replace("/[^a-zA-Z0-9\s]/", "", $blurb);

	return $blurb;
}

/**
 * Makes a string alpha-numeric by stripping any and all symbols (non-alphanumeric characters)
 * from the string
 * @param string $blurb
 */
function strip_symbols($blurb){
	$blurb = preg_replace("/[^a-zA-Z0-9\s]/", "", $blurb);
	return $blurb;
}

/**
 * Nukes HTML tags and spaces at the same time
 * @param string $blurb
 */
function strip_tags_whitespace($blurb){
	$blurb = stripslashes(strip_tags($blurb));
	$blurb = preg_replace('/<[^>]*>/', '', $blurb);
	$blurb = trim(preg_replace('/(?:\s\s+|\n|\t)/', ' ', $blurb));

	return $blurb;
}

/**
 * Strips white space from around a string
 * @param string $str
 */
function strip_whitespace($str){
	return trim(preg_replace('/(?:\s\s+|\n|\t)/', '', $str));
}

/**
 * Strips all double spaces to single spaces
 * @param string $str
 */
function strip_to_single($str){
	return trim(preg_replace('/(?:\s\s+|\n|\t)/', ' ', $str));
}

/**
 * Truncates a string by a set number of characters
 * @param string $title_string
 * @param int $truncate_after_nr_chars
 */
function truncate_string($title_string, $truncate_after_nr_chars = 50){

	$nr_of_chars = strlen($title_string);
	if($nr_of_chars >= $truncate_after_nr_chars){
		$title_string = substr_replace( $title_string, "...", $truncate_after_nr_chars, $nr_of_chars - $truncate_after_nr_chars);
	}
	return $title_string;
}

/**
 * var_dump replacement which houses it own self contained HTML and can act on more complex variables
 *
 * It has been renamed from its original from do_dump to dd
 *
 * @param $var
 * @param $var_name
 * @param $indent
 * @param $reference
 *
 * ////////////////////////////////////////////////////////
 * // Function:         do_dump
 * // Inspired from:     PHP.net Contributions
 * // Description: Better GI than print_r or var_dump
 *
 */
function dd(&$var, $var_name = NULL, $indent = NULL, $reference = NULL)
{
	$do_dump_indent = "<span style='color:#eeeeee;'>|</span> &nbsp;&nbsp; ";
	$reference = $reference.$var_name;
	$keyvar = 'the_do_dump_recursion_protection_scheme'; $keyname = 'referenced_object_name';

	if (is_array($var) && isset($var[$keyvar]))
	{
		$real_var = &$var[$keyvar];
		$real_name = &$var[$keyname];
		$type = ucfirst(gettype($real_var));
		echo "$indent$var_name <span style='color:#a2a2a2'>$type</span> = <span style='color:#e87800;'>&amp;$real_name</span><br>";
	}
	else
	{
		$var = array($keyvar => $var, $keyname => $reference);
		$avar = &$var[$keyvar];

		$type = ucfirst(gettype($avar));
		if($type == "String") $type_color = "<span style='color:green'>";
		elseif($type == "Integer") $type_color = "<span style='color:red'>";
		elseif($type == "Double"){ $type_color = "<span style='color:#0099c5'>"; $type = "Float"; }
		elseif($type == "Boolean") $type_color = "<span style='color:#92008d'>";
		elseif($type == "NULL") $type_color = "<span style='color:black'>";

		if(is_array($avar))
		{
			$count = count($avar);
			echo "$indent" . ($var_name ? "$var_name => ":"") . "<span style='color:#a2a2a2'>$type ($count)</span><br>$indent(<br>";
			$keys = array_keys($avar);
			foreach($keys as $name)
			{
				$value = &$avar[$name];
				do_dump($value, "['$name']", $indent.$do_dump_indent, $reference);
			}
			echo "$indent)<br>";
		}
		elseif(is_object($avar))
		{
			echo "$indent$var_name <span style='color:#a2a2a2'>$type</span><br>$indent(<br>";
			foreach($avar as $name=>$value) do_dump($value, "$name", $indent.$do_dump_indent, $reference);
			echo "$indent)<br>";
		}
		elseif(is_int($avar)) echo "$indent$var_name = <span style='color:#a2a2a2'>$type(".strlen($avar).")</span> $type_color$avar</span><br>";
		elseif(is_string($avar)) echo "$indent$var_name = <span style='color:#a2a2a2'>$type(".strlen($avar).")</span> $type_color\"$avar\"</span><br>";
		elseif(is_float($avar)) echo "$indent$var_name = <span style='color:#a2a2a2'>$type(".strlen($avar).")</span> $type_color$avar</span><br>";
		elseif(is_bool($avar)) echo "$indent$var_name = <span style='color:#a2a2a2'>$type(".strlen($avar).")</span> $type_color".($avar == 1 ? "TRUE":"FALSE")."</span><br>";
		elseif(is_null($avar)) echo "$indent$var_name = <span style='color:#a2a2a2'>$type(".strlen($avar).")</span> {$type_color}NULL</span><br>";
		else echo "$indent$var_name = <span style='color:#a2a2a2'>$type(".strlen($avar).")</span> $avar<br>";

		$var = $var[$keyvar];
	}
}