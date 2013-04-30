<?php

namespace glue\common;

function filter_array_fields($ar, $fields = array()){
	$new = null;
	foreach($ar as $k => $v){
		if(array_search($k, $fields) !== null){
			$new[$k] = !is_array($v) && preg_match('/^[0-9]+$/', $v) > 0 ? (int)$v : $v;
		}
	}
	return $new;
}

function farray_merge_recursive() {

    if (func_num_args() < 2) {
        trigger_error(__FUNCTION__ .' needs two or more array arguments', E_USER_WARNING);
        return;
    }
    $arrays = func_get_args();
    $merged = array();

    while ($arrays) {
        $array = array_shift($arrays);
        if (!is_array($array)) {
            trigger_error(__FUNCTION__ .' encountered a non array argument', E_USER_WARNING);
            return;
        }
        if (!$array)
            continue;
        foreach ($array as $key => $value)
            if (is_string($key))
                if (is_array($value) && array_key_exists($key, $merged) && is_array($merged[$key]))
                    $merged[$key] = call_user_func(__FUNCTION__, $merged[$key], $value);
                else
                    $merged[$key] = $value;
            else
                $merged[] = $value;
    }
    return $merged;
}

function convert_size_human($size){
	$unit=array('','KB','MB','GB','TB','PB');
	$byte_size = $size/pow(1024,($i=floor(log($size,1024))));

	if(preg_match('/^[0-9]+$/', $byte_size)){
		return $byte_size.' '.$unit[$i];
	}else{
		preg_match('/^[0-9]+\.[0-9]{2}/', $byte_size, $matches);
		return $matches[0].' '.$unit[$i];
	}
}

function getDirectoryFileList($directory, $exts = array()){
	$files = array();

	if ($handle = opendir($directory)) {
		while (false !== ($file = readdir($handle))) {
			if ($file != "." && $file != "..") {
				if(count($exts) <= 0){
					$files[] = $file;
				}else{
					foreach($exts as $extension){
						if(preg_match("/".$extension."/i", $file) > 0){
							$files[] = $file;
						}
					}
				}
			}
		}
		closedir($handle);
	}
	return $files;
}

function summarise_array_row($new_array, $old_array){
	$ret = array();
	foreach($old_array as $k=>$v){
		if(isset($new_array[$k])){
			$ret[$k] = $v+$new_array[$k];
		}else{
			$ret[$k] = 0;
		}
		unset($new_array[$k]);
	}

	if(!is_array($new_array))
		$new_array = array();

	$ret = array_merge($ret, $new_array);
	return $ret;
}

/**
 * Generate a new password
 *
 * @author Sam Millman
 *
 * This function will make a new password for anyone who asks.
 * This function is not always used for user passwords and can be used
 * for anything that requires a relatively secure combination of numbers, letters
 * and symbols for either encryption or hashing.
 */
function generate_new_pass(){

	$length=9; // Length of the returned password
	$strength=8; // A strength denominator

	$vowels = 'aeuy'; // Vowels to use
	$consonants = 'bdghjmnpqrstvz'; // Consonants to use

	// Repitition throughout the strengths to make the new password
	if ($strength & 1) {
		$consonants .= 'BDGHJLMNPQRSTVWXZ';
	}
	if ($strength & 2) {
		$vowels .= "AEUY";
	}
	if ($strength & 4) {
		$consonants .= '23456789';
	}
	if ($strength & 8) {
		$consonants .= '@#$%';
	}

	// Randomise the placement of text entities
	$password = '';
	$alt = time() % 2;
	for ($i = 0; $i < $length; $i++) {
		if ($alt == 1) {
			$password .= $consonants[(rand() % strlen($consonants))];
			$alt = 0;
		} else {
			$password .= $vowels[(rand() % strlen($vowels))];
			$alt = 1;
		}
	}

	// Return the password
	return $password;
}

/**
 * Strips all from string including symbols and punctuation
 *
 * @param $blurb
 */
function strip_all($blurb){

	$blurb = stripslashes(strip_tags($blurb));

	$blurb = preg_replace('/<[^>]*>/', '', $blurb);

	$blurb = trim(preg_replace('/(?:\s\s+|\n|\t)/', ' ', $blurb));

	$blurb = preg_replace("/[^a-zA-Z0-9\s]/", "", $blurb);

	return $blurb;
}

function make_alpha_numeric($blurb){
	$blurb = preg_replace("/[^a-zA-Z0-9\s]/", "", $blurb);
	return $blurb;
}

function stripTags_whitespace($blurb){
	$blurb = stripslashes(strip_tags($blurb));

	$blurb = preg_replace('/<[^>]*>/', '', $blurb);

	$blurb = trim(preg_replace('/(?:\s\s+|\n|\t)/', ' ', $blurb));

	return $blurb;
}

function strip_whitespace($str){
	return trim(preg_replace('/(?:\s\s+|\n|\t)/', '', $str));
}

function strip_to_single($str){
	return trim(preg_replace('/(?:\s\s+|\n|\t)/', ' ', $str));
}

function truncate_string($title_string, $truncate_after_nr_chars = 50){

	$nr_of_chars = strlen($title_string);
	if($nr_of_chars >= $truncate_after_nr_chars){
		$title_string = substr_replace( $title_string, "...", $truncate_after_nr_chars, $nr_of_chars - $truncate_after_nr_chars);
	}
	return $title_string;
}

// Read a file and display its content chunk by chunk
function readfile_chunked($filename, $retbytes = TRUE) {
	$buffer = '';
	$cnt =0;
	// $handle = fopen($filename, 'rb');
	$handle = fopen($filename, 'rb');
	if ($handle === false) {
		return false;
	}
	while (!feof($handle)) {
		$buffer = fread($handle, 1024*1024);
		echo $buffer;
		ob_flush();
		flush();
		if ($retbytes) {
			$cnt += strlen($buffer);
		}
	}
	$status = fclose($handle);
	if ($retbytes && $status) {
		return $cnt; // return num. bytes delivered like readfile() does.
	}
	return $status;
}

function ago($datefrom,$dateto=-1)
{
	// Defaults and assume if 0 is passed in that
	// its an error rather than the epoch

	if($datefrom==0) { return "A long time ago"; }
	if($dateto==-1) { $dateto = time(); }

	// Make the entered date into Unix timestamp from MySQL datetime field

	$datefrom = $datefrom;

	// Calculate the difference in seconds betweeen
	// the two timestamps

	$difference = $dateto - $datefrom;

	// Based on the interval, determine the
	// number of units between the two dates
	// From this point on, you would be hard
	// pushed telling the difference between
	// this function and DateDiff. If the $datediff
	// returned is 1, be sure to return the singular
	// of the unit, e.g. 'day' rather 'days'

	switch(true)
	{
		// If difference is less than 60 seconds,
		// seconds is a good interval of choice
		case(strtotime('-1 min', $dateto) < $datefrom):
			$datediff = $difference;
			$res = ($datediff==1) ? $datediff.' second ago' : $datediff.' seconds ago';
			break;
			// If difference is between 60 seconds and
			// 60 minutes, minutes is a good interval
		case(strtotime('-1 hour', $dateto) < $datefrom):
			$datediff = floor($difference / 60);
			$res = ($datediff==1) ? $datediff.' minute ago' : $datediff.' minutes ago';
			break;
			// If difference is between 1 hour and 24 hours
			// hours is a good interval
		case(strtotime('-1 day', $dateto) < $datefrom):
			$datediff = floor($difference / 60 / 60);
			$res = ($datediff==1) ? $datediff.' hour ago' : $datediff.' hours ago';
			break;
			// If difference is between 1 day and 7 days
			// days is a good interval
		case(strtotime('-1 week', $dateto) < $datefrom):
			$day_difference = 1;
			while (strtotime('-'.$day_difference.' day', $dateto) >= $datefrom)
			{
				$day_difference++;
			}

			$datediff = $day_difference;
			$res = ($datediff==1) ? 'yesterday' : $datediff.' days ago';
			break;
			// If difference is between 1 week and 30 days
			// weeks is a good interval
		case(strtotime('-1 month', $dateto) < $datefrom):
			$week_difference = 1;
			while (strtotime('-'.$week_difference.' week', $dateto) >= $datefrom)
			{
				$week_difference++;
			}

			$datediff = $week_difference;
			$res = ($datediff==1) ? 'last week' : $datediff.' weeks ago';
			break;
			// If difference is between 30 days and 365 days
			// months is a good interval, again, the same thing
			// applies, if the 29th February happens to exist
			// between your 2 dates, the function will return
			// the 'incorrect' value for a day
		case(strtotime('-1 year', $dateto) < $datefrom):
			$months_difference = 1;
			while (strtotime('-'.$months_difference.' month', $dateto) >= $datefrom)
			{
				$months_difference++;
			}

			$datediff = $months_difference;
			$res = ($datediff==1) ? $datediff.' month ago' : $datediff.' months ago';

			break;
			// If difference is greater than or equal to 365
			// days, return year. This will be incorrect if
			// for example, you call the function on the 28th April
			// 2008 passing in 29th April 2007. It will return
			// 1 year ago when in actual fact (yawn!) not quite
			// a year has gone by
		case(strtotime('-1 year', $dateto) >= $datefrom):
			$year_difference = 1;
			while (strtotime('-'.$year_difference.' year', $dateto) >= $datefrom)
			{
				$year_difference++;
			}

			$datediff = $year_difference;
			$res = ($datediff==1) ? $datediff.' year ago' : $datediff.' years ago';
			break;

	}
	return $res;
}

function getMonthsOfYear(){
	$months = array(
	1 => 'January',
	2 => 'February',
	3 => 'March',
	4 => 'April',
	5 => 'May',
	6 => 'June',
	7 => 'July',
	8 => 'August',
	9 => 'September',
	10 => 'October',
	11 => 'November',
	12 => 'December'
	);

	return $months;
}

function getDaysOfMonth(){
	$ret = array();
	$days = range(1, 32);

	foreach($days as $day){
		$ret[$day] = $day;
	}
	return $ret;
}

function getYearRange($start = 0, $end = 100){
	$data = Array();

	$thisYear = $start == 0 ? date('Y') : $start;
	$startYear = ($thisYear - $end);

	foreach (range($thisYear, $startYear) as $year) {
		$data[$year] = $year;
	}

	return $data;
}

////////////////////////////////////////////////////////
// Function:         do_dump
// Inspired from:     PHP.net Contributions
// Description: Better GI than print_r or var_dump

function do_dump(&$var, $var_name = NULL, $indent = NULL, $reference = NULL)
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