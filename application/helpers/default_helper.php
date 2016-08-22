<?php if (!defined('BASEPATH')) exit('No direct script access allowed');


function format_num_callback($n)
{
    return floatval( str_replace(',', '', $n) );
}

function truncate_number( $number = 0 , $decimals = 2 )
{
	$point_index = strrpos( $number , '.' );
	if($point_index===FALSE) return $number;
	return substr( $number , 0 , $point_index + $decimals + 1 );
}

function debug( $msg , $die=FALSE )
{
	echo ( "<style> pre{background-color:chocolate;font-weight:bolder;} .debug{color: black;text-align:center;background-color:yellow;font-weight:bolder;padding:10px;font-size:14px;}</style>" );

	echo ("\n<p class='debug'>\n");

	echo ("MSG".time().": ");

	if ( is_array ( $msg ) )
	{
		echo ( "\n<pre>\n" );
		print_r ( $msg );
		echo ( "\n</pre>\n" );
	}
	elseif ( is_object ( $msg ) )
	{
		echo ( "\n<pre>\n" );
		var_dump ( $msg );
		echo ( "\n</pre>\n" );
	}
	else
	{
		echo ( $msg );
	}

	echo ( "\n</p>\n" );

	if ( $die )
	{
		die;
	}
}
if (!function_exists('array_column')) {
	function array_column($input, $column_key, $index_key = null)
	{
		if ( empty( $input ) )
		{
			return array();
		}

		if ($index_key !== null) {
			// Collect the keys
			$keys = array();
			$i = 0; // Counter for numerical keys when key does not exist

			foreach ($input as $row) {
				if (array_key_exists($index_key, $row)) {
					// Update counter for numerical keys
					if (is_numeric($row[$index_key]) || is_bool($row[$index_key])) {
						$i = max($i, (int)$row[$index_key] + 1);
					}

					// Get the key from a single column of the array
					$keys[] = $row[$index_key];
				} else {
					// The key does not exist, use numerical indexing
					$keys[] = $i++;
				}
			}
		}

		if ($column_key !== null) {
			// Collect the values
			$values = array();
			$i = 0; // Counter for removing keys

			foreach ($input as $row) {
				if (array_key_exists($column_key, $row)) {
					// Get the values from a single column of the input array
					$values[] = $row[$column_key];
					$i++;
				} elseif (isset($keys)) {
					// Values does not exist, also drop the key for it
					array_splice($keys, $i, 1);
				}
			}
		} else {
			// Get the full arrays
			$values = array_values($input);
		}

		if ($index_key !== null) {
			return array_combine($keys, $values);
		}

		return $values;
	}

}
function array_pluck($key, $array)
{
    if (is_array($key) || !is_array($array)) return array();
    $funct = create_function('$e', 'return is_array($e) && array_key_exists("'.$key.'",$e) ? $e["'. $key .'"] : null;');
    return array_map($funct, $array);
}

function replace_quotes($string)
{
	return preg_replace(array("/`/", "/'/", "/&acute;/"), "",$string);
}
function unique_multidim_array($array, $key) { 
    $temp_array = array(); 
    $i = 0; 
    $key_array = array(); 
    
    foreach($array as $val) { 
        if (!in_array($val[$key], $key_array)) { 
            $key_array[$i] = $val[$key]; 
            $temp_array[$i] = $val; 
        } 
        $i++; 
    } 
    return $temp_array; 
}


if ( ! function_exists('remove_null_values')) {
	function remove_null_values($input_arry = array())
	{
		if(empty($input_arry))
			return $input_arry;

		array_walk_recursive($input_arry, 'replacer');
		return $input_arry;
	}
}

function replacer(& $item, $key){
    if ($item === null) 
    {
        $item = '';
    }
}

if (!function_exists('sprintf_message')) {

    function sprintf_message($str = '', $vars = array(), $start_char = '{{', $end_char = '}}') {
        if (!$str)
            return '';
        if (count($vars) > 0) {
            foreach ($vars as $k => $v) {
                $str = str_replace($start_char . $k . $end_char, $v, $str);
            }
        }

        return $str;
    }

}

if (!function_exists('humanTiming')) {

    /**
     * Human Readble Time
     * @param type $time
     * @return type
     */
    function humanTiming($time) {
        $time = time() - strtotime($time); // to get the time since that moment
        $tokens = array(
            31536000 => 'year',
            2592000 => 'month',
            604800 => 'week',
            86400 => 'day',
            3600 => 'hour',
            60 => 'minute',
            1 => 'second'
        );
        foreach ($tokens as $unit => $text) {
            if ($time < $unit)
                continue;
            $numberOfUnits = floor($time / $unit);
            return $numberOfUnits . ' ' . $text . (($numberOfUnits > 1) ? 's' : '') . ' ago';
        }
    }

}

/* function for return filtered profile data for setting sessionstorage */
	if (!function_exists('prepareUserProfile')) {

    /**
     * Filtered User Profile Data
     * @param type $array
     * @return type $array
     */
    function prepareUserProfile($user_profile) {

    	if(!$user_profile)return $user_profile;

    	$return_profile = $user_profile;
    	
    	if(isset($return_profile['user_id'])){
    		unset($return_profile['user_id']);
    	}

    	if(isset($return_profile['user_unique_id'])){
    		unset($return_profile['user_unique_id']);
    	}    

    	if(isset($return_profile['password'])){
    		unset($return_profile['password']);
    	}

    	if(!empty($return_profile['dob']) && !is_null($return_profile['dob'])){
    		$return_profile['dob'] = date("M d, Y",strtotime($return_profile['dob']));
    		$return_profile['dob_picker'] = date("Y-m-d",strtotime($return_profile['dob']));
    	} else {
    		$return_profile['dob'] = '';
    		$return_profile['dob_picker'] = '';
    	}
    	
    	$return_profile['image']      = ($return_profile['image']) ? $return_profile['image'] : base_url().DEFAULT_PROFILE;

    	$return_profile['phonecode']  = (!empty($return_profile['phonecode'])) ? '+'.$return_profile['phonecode'] : '';

    	$return_profile['is_profile_complete'] = (!empty($return_profile['dob']) && !empty($return_profile['user_name']) && !empty($return_profile['email'])) ? true : false;

    	return $return_profile;

    }//function end here.
}

if ( ! function_exists('replace_null_to_zero')) {
	function replace_null_to_zero($input_arry = array())
	{
		if(empty($input_arry))
			return $input_arry;

		array_walk_recursive($input_arry, 'null_replacer');
		return $input_arry;
	}
}

function null_replacer(& $item, $key){
    if ($item === null) 
    {
        $item = '0';
    }
}


//(!empty() && $_player_detail['height']!= 'NA') ? str_replace(".","'",round($_player_detail['height'],1)) : $_player_detail['height'];
/* -------------------------------------------------------------------- */