<?php
function between_replace ($open, $close, &$in, $with, $limit=false, $from=0)
{
if ($limit!==false && $limit==0)
{
	return $in;
}       
$open_position = strpos ($in, $open, $from);
if ($open_position===false)
{
	return false;
};
$close_position = strpos ($in, $close, $open_position+strlen($open));
if ($close_position===false)
{
	return false;
};
$current = false;
if (strpos($with,'{*}')!==false)
{
	$current = substr ($in, $open_position+strlen($open), $close_position-$open_position-strlen($open));
	$current = str_replace ('{*}',$current,$with);
	//debug_echo ($current);
}
else
{
	$current = $with;
}
$in = substr_replace ($in, $current, $open_position+strlen($open), $close_position-$open_position-strlen($open));
$next_position = $open_position + strlen($current) + 1;
if ($next_position>=strlen($in))
{
	return false;
}
if ($limit!==false)
{
	$limit--;
}       
between_replace ($open, $close, $in, $with, $limit, $next_position);
return $in;
}

function between_fetch ($open, $close, &$in, &$result=null, $with='', $limit=false, $from=0)
{
if ($limit!==false && $limit==0)
{
	return $in;
};
if (!is_array($result))
{
	$result = array ();
}
$open_position = strpos ($in, $open, $from);
if ($open_position===false)
{
	return false;
};
$close_position = strpos ($in, $close, $open_position+strlen($open));
if ($close_position===false)
{
	return false;
};
$current = substr ($in, $open_position+strlen($open), $close_position-$open_position-strlen($open));
$next_position = $open_position + strlen($current) + 1;
if ($with!='')
{
	$current = str_replace('{*}', $current, $with);
}
$result[] = $current;
if ($next_position>=strlen($in))
{
	return false;
}
if ($limit!==false)
{
	$limit--;
}
between_fetch ($open, $close, $in, $result, $with, $limit, $next_position);
return $in;
}
?>