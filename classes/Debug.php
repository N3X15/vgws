<?php
namespace VGWS;
class Debug {
	public static function GetStackTrace($offset=0) {
		$stack=xdebug_get_function_stack();
		$table=new Table('stacktrace');
		$headerRow=$table->createRow();
		$headerRow->addHeader('Depth');
		$headerRow->addHeader('File/Line');
		$headerRow->addHeader('Class/Method');
		for($i=0;$i<xdebug_get_stack_depth()-$offset;$i++){
			$stackRow = $table->createRow();
			/*
		      'function' => string 'fix_string' (length=10)
		      'class' => string 'strings' (length=7)
		      'file' => string '/var/www/xdebug_get_function_stack.php' (length=63)
		      'line' => int 12
		      'params' =>
		        array
		          'a' => string ''Derick'' (length=8)
			 */
			$stackRow->addHeader($i);
			$row = $stack[$i];
			$stackRow->addCell("{$row['file']}:{$row['line']}");
			$func = isset($row['function'])?$row['function']:'__MAIN__';
			$funcFormatting="{$func}(".join(', ',$row['params']).")";
			if(isset($row['class']))
				$funcFormatting="{$row['class']}::{$funcFormatting}";
			$stackRow->addCell($funcFormatting,array('class'=>'code'));
		}
		return $table->__toString();
	}
	public static function AssertIs($message,$val,$expected) {
		if($val==$expected)
			return;
		$o=<<<EOF
<h1>Assertion Failure</h1>
<p>
	Code has gone haywire, and the system has successfully intercepted this problem.
	However, in order to preserve data integrity, the system cannot allow your action to continue.
</p>
<p>
	Please notify our coders.  To help them find out what went wrong, here's some debugging information:
</p>
<h2>Debugging Information</h2>
<dl>
EOF;
		$o.="<dt>Message:</dt><dd>{$message}</dd>";
		$o.='<dt>Expected value:</dt><dd><code>'.htmlentities(var_export($expected,true)).'</code></dd>';
		$o.='<dt>Actual value:</dt><dd><code>'.htmlentities(var_export($val,true)).'</code></dd>';
		$o.='<dt>Call Stack:</dt><dd>'.self::GetStackTrace(2).'</dd>';
		$o.='</dl>';
		error($o,ERROR_CUSTOM_MESSAGE);
	}
	public static function AssertNot($message,$val,$expected) {
		if($val!=$expected)
			return;
		$o=<<<EOF
<h1>Assertion Failure</h1>
<p>
	Code has gone haywire, and the system has successfully intercepted this problem.
	However, in order to preserve data integrity, the system cannot allow your action to continue.
</p>
<p>
	Please notify our coders.  To help them find out what went wrong, here's some debugging information:
</p>
<h2>Debugging Information</h2>
<dl>
EOF;
		$o.="<dt>Message:</dt><dd>{$message}</dd>";
		$o.='<dt>Expected value:</dt><dd><code>'.htmlentities(var_export($expected,true)).'</code></dd>';
		$o.='<dt>Actual value:</dt><dd><code>'.htmlentities(var_export($val,true)).'</code></dd>';
		$o.='<dt>Call Stack:</dt><dd>'.self::GetStackTrace(2).'</dd>';
		$o.='</dl>';
		error($o,ERROR_CUSTOM_MESSAGE);
	}
}
