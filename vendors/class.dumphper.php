<?php
/**
* Impored var_dump for PHP 5.2.3++
* @author Dmitry "widowmaker" Khavilo
* @email wm .dot. morgun .at. gmail .dot. com
**/

// Fixed by S

class Dumphper
{
	/** config **/
	
	static $encoding = 'UTF-8';  /** Text encoding, needed to escape stings **/
	static $escape_keys = false; /** Should array keys be escaped (slow) **/
	static $max_showw_depth = 8; /** Defines how many nested levels will be expanded by default **/
	
	/** don't touch **/
	static $objects = array();
	static $calls = 0;
	static $depth = 0;
	
	static function dump(&$source)
	{
		self::$depth = 0;
		self::drawStyles();
		//$s = microtime(true);
		//self::$objects = array();
		self::$calls++;
		echo '<div class="dumphper">';
		self::_dump($source);
		echo '</div>';
		//echo round(microtime(true) - $s,6)*1000 . 's';
	}
	
	static function _dump(&$source, &$parents = array())
	{
		self::$depth++;
		switch(gettype($source))
		{
			case 'array':
				self::drawArray($source, $parents);
				break;
			case 'object':
				self::drawObject($source, $parents);
				break;
			case 'NULL':
				self::drawNULL();
				break;
			case 'boolean':
				self::drawBoolean($source);
				break;
			case 'resource':
				self::drawResource($source);
				break;
			default:
				self::drawScalar($source);
				break;
		}
		self::$depth--;
	}
	
	static function escape(&$source)
	{
		return ( is_string($source) ? ( $source ? htmlentities($source, ENT_QUOTES, self::$encoding) : '&nbsp;') : $source );
	}
	
	static function drawScalar(&$source, &$escape = true)
	{
        if ($source === '0') $escape = False;
		self::drawValue($escape ? self::escape($source) : $source, gettype($source));
	}
	
	static function drawNULL()
	{
		self::drawValue('NULL', 'null');
	}
	
	static function drawResource(&$source)
	{
		self::drawValue(get_resource_type($source) . ' ' . strtolower((string)$source) , 'resource');
	}

	static function drawBoolean(&$source)
	{
		self::drawValue($source ? 'true' : 'false', 'boolean');
	}

	static function drawArray(&$source, &$parents)
	{
		if (self::isRecursiveArray($source, $parents))
		{
			self::drawValue('RECURSION', 'recursion');
		}
		else
		{
			$c = count($source);
			echo '<div class="dumphper-container' . (self::$depth <= self::$max_showw_depth ? '' : ' dumphper-closed') . '">';
			self::drawHeader( $c > 0 ? '<a class="dumphper-toggler" href="javascript:;" onclick="dumphper_toggle(this);">Array (' . $c . ')</a>' : 'empty Array ', 'array');
			if ( $c > 0 )
			{
				echo '<table class="dumphper-table" cellspacing="0" cellpadding="0">';
				foreach ($source as $key => &$value)
				{
					echo '<tr><td class="dumphper-key-array">';
					self::drawScalar($key, self::$escape_keys);
					echo '</td><td>';
					self::_dump($value, $parents);
					echo '</td></tr>';
				}
				echo '</table>';
			}
			echo '</div>';
		}
	}
	
	static function getObjectId(&$source)
	{
		$class = get_class($source);
		if ( !isset(self::$objects[$class]) )
			self::$objects[$class] = array();
		foreach (self::$objects[$class] as $id => $obj)
			if ( $source === $obj )
				return $id;
		self::$objects[$class][] = &$source;
		return count(self::$objects[$class])-1;
	}
	
	static function drawObject(&$source, &$parents)
	{
		$className = get_class($source);
		$object_index = self::getObjectId($source);
		$object_id = 'dhph_' . $className . '_' . self::$calls . '_' . $object_index;

		if (self::isRecursiveObject($source, $parents))
		{
			self::drawValue('<a href="#' .  $object_id . '" onclick="dumphper_show(this);">' . $className . ' #' . $object_index . '</a>' , 'recursion');
		}
		else
		{
			$sClass = new ReflectionObject($source);
			$statics = null;
			$class = $sClass->getParentClass();
			$classArray = '';
			while ( is_object($class) )
			{
				$classArray = ' &gt; ' . $class->getName() . $classArray;
				$class = $class->getParentClass();
			}
			
			echo '<div class="dumphper-container' . (self::$depth <= self::$max_showw_depth ? '' : ' dumphper-closed') . '">';
			self::drawHeader( '<a id="' .  $object_id . '" name="' .  $object_id . '" class="dumphper-toggler" href="javascript:;" onclick="dumphper_toggle(this);">' . $className . ' #' . $object_index .
				(count($classArray) ? ' <span class="dumphper-class-def"> ' . $classArray . '</span>' : '' ).
				'</a>', 'object');
			echo '<table class="dumphper-table" cellspacing="0" cellpadding="0">';
			
			if(!DPHP_USE_ACCESSIBLE)
				$temp = (array)$source;

				$class = $sClass;
			while ( is_object($class) )
			{
				$properties = $class->getProperties();
				if ($class->name != $className && count($properties))
				{
					echo '<tr><td colspan = "2">';
					self::drawValue('<code>inherited from </code>' . $class->name . ':', 'inherited');
					echo '</td></tr>';
				}
				foreach ($properties as &$value)
				{
					$declaredClass = $value->getDeclaringClass()->name;
					if ($class->name == $declaredClass)
					{
						echo '<tr><td class="dumphper-key-object">';
						self::drawValue($value->name/* . ' <span class="dumphper-class-def">' . $declaredClass . '</span>'*/, join('-',Reflection::getModifierNames($value->getModifiers())));
						echo '</td><td>';
						if ($value->isPublic()) {
							$tmp = $value->getValue($source);
							self::_dump($tmp, $parents);
						}
						/** in case of PHP 5.3+ **/
						elseif (DPHP_USE_ACCESSIBLE)
						{
							$value->setAccessible(true);
							self::_dump($value->getValue($source), $parents);
						}
						/** in case of PHP 5.2.x+ **/
						elseif ($value->isStatic())
						{
							if (!$statics) $statics = $class->getStaticProperties();
							self::_dump($statics[$value->name], $parents);
						}
						else
						{
							$scope = $value->isPrivate() ? $value->class : '*';
							self::_dump($temp["\0{$scope}\0{$value->name}"], $parents);
						}
						echo '</td></tr>';
					}
				}
				$class = $class->getParentClass();
			}
			echo '</table>';
			echo '</div>';
		}
	}
	
	static function isRecursiveArray(&$source, &$parents)
	{
		if ( count($parents) > 0 )
		{
			$uKey = uniqid('array', true);
			$source[$uKey] = true;
			foreach ( $parents as &$parrent )
			{
				if ( is_array($parrent) && isset($parrent[$uKey]) )
				{
					unset($source[$uKey]);
					return true;
				}
			}
			unset($source[$uKey]);
		}
		$parents[] = &$source;
		return false;
	}
	
	static function isRecursiveObject(&$source, &$parents)
	{
		if ( in_array($source, $parents, true) )
			return true;
		$parents[] = &$source;
		return false;
	}
	
	static function drawValue($value, $type)
	{
/*        if ($type == 'string') {
            echo '<pre>', varDump($value);
            die;
        }*/
		echo '<span class="dumphper-value dumphper-' . $type . '" title="' . $type . '">' . $value . '</span>';
	}
	
	static function drawHeader($value, $type)
	{
		echo '<div class="dumphper-head dumphper-head-' . $type . '" title="' . $type . '">' . $value .'</div>';
	}

	static function drawStyles()
	{
		static $displayed = false;
		if ($displayed) return; //Exit if already shown
		$displayed = true;
?><style type="text/css">
.dumphper { margin: 0 0 3px !important; /*max-width:500px !important;*/}
.dumphper-container { display: inline-block !important; position: relative !important; }
.dumphper-value { background-color: #e7e7e7 !important; border: 1px solid #888 !important; }
.dumphper-table { border-left: 1px solid #888 !important; width: 100%; }
.dumphper-table td { border-right: 1px solid #888 !important; border-bottom: 1px solid #888 !important; vertical-align: top !important;}
.dumphper-table .dumphper-value { border-width: 0 0 0 0 !important;display: block;}
.dumphper, .dumphper-value, .dumphper-key, .dumphper-head { font: normal 12px Arial !important; line-height: 15px !important;}
.dumphper-value { padding: 0 3px 0 12px !important; background-repeat: no-repeat !important; cursor: default !important;max-height: 45px; overflow: auto;}
.dumphper-key { padding: 0 3px !important; }
.dumphper-head { color: #fff !important; background-image: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAAJCAYAAADzRkbkAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAACRJREFUeNpi+P//fw8TAwPDfxDxFUR8gROf4axvDEB10gABBgAvWg1pnyJeXQAAAABJRU5ErkJggg==) !important; background-repeat: repeat-x !important; padding: 1px 3px 2px !important; border: 1px solid #888 !important; font-weight: bold !important; }
.dumphper-head-array { background-color: #260 !important;}
.dumphper-key-array, .dumphper-key-array .dumphper-value { background-color: #dcffd0 !important;}
.dumphper-key-array .dumphper-string { color: #030 !important; }
.dumphper-key-array .dumphper-string, .dumphper-key-array .dumphper-integer { background-image: none !important; padding-left: 3px !important;}
.dumphper-key-object { color: #004 !important;}
.dumphper-head-object { background-color: #006 !important;}
.dumphper-key-object, .dumphper-key-object .dumphper-value { background-color: #c8defe !important; }
.dumphper-class-def { color: #666 !important; font: bold oblique 11px  Arial !important;}
.dumphper-head-object .dumphper-class-def { color: #bbb !important;}
.dumphper-string { color: #a40 !important; background-image: url(data:image/gif;base64,R0lGODlhCgASAIAAAP/Qmv///yH5BAAAAAAALAAAAAAKABIAAAISjI+py+0MYkRzSQBlpvf5D34FADs=) !important; }
.dumphper-integer { color: #00f !important; background-image: url(data:image/gif;base64,R0lGODlhCgASAIAAAKTC/////yH5BAAAAAAALAAAAAAKABIAAAIVjI+pqwDsGHRvRVtbThdRKoXiSC4FADs=) !important; }
.dumphper-double { color: #f00 !important; background-image: url(data:image/gif;base64,R0lGODlhCgASAIAAAP+/v////yH5BAAAAAAALAAAAAAKABIAAAIWjI+pu+CQHJPxUXTrhI1j6zHiSJZiAQA7) !important; }
.dumphper-resource { color: #660 !important; background-image: url(data:image/gif;base64,R0lGODlhCgASAIAAAP///8zMfyH5BAAAAAAALAAAAAAKABIAAAIVhI+py+0bYgAxSWon1Kpm6T3iSDYFADs=) !important; }
.dumphper-null { color: #000 !important; background-image: url(data:image/gif;base64,R0lGODlhIgASAIAAAL+/v////yH5BAAAAAAALAAAAAAiABIAAAI+jI+py+0Po5wBWHAupswmzy1gtiEaeFWj+pXmhsLuyiqxcZOtKOO9/uL5hjUi8CVLpk4YZugJjUqn1KqVUQAAOw==) !important; padding-left: 37px !important; }
.dumphper-boolean { color: #000 !important; background-image: url(data:image/gif;base64,R0lGODlhIgASAIAAAL+/v////yH5BAAAAAAALAAAAAAiABIAAAI+jI+py+0PYwRULlpVthpcz3VfsJEYgoFGeZzZC8Ktisb2epvzqPdyD6x5fkQVyzSkpYS2ZegJjUqn1KqVUwAAOw==) !important; padding-left: 37px !important; }
.dumphper-inherited { color: #009 !important; background-color: #c8defe !important; /*background-image: url(data:image/gif;base64,R0lGODlhSgASAIABAH9/5f///yH5BAEAAAEALAAAAABKABIAAAJ2jI+py+0Po5y02ospAHDzbGyZt4jdB5ohGqmXCyLwA3uo7eIyGdj951vxfjnO7LdbHUxM4E3EKiaRyOFUSaU2g9lrF+fEJqTZpti7jKoVZva6HC6nS2/jTWklknj7rdDJBZb2JJUXc4iYqLjI2Oj4CBkpOblYAAA7) !important;*/ padding-left: /*7*/4px !important; font-weight: bold !important; }
.dumphper-inherited code { font: 12px Arial; color: #66c !important; }
.dumphper-recursion { color: #609 !important; background-image: url(data:image/gif;base64,R0lGODlhEAASAIAAAP/Uf////yH5BAAAAAAALAAAAAAQABIAAAIejI+py+0PIwMUmGoRvZXrHGzeIWIj2YWpxLbuC0sFADs=) !important; padding-left: 19px !important; font-weight: bold !important; }
.dumphper-recursion a { color: #609 !important; text-decoration: underline !important; }
.dumphper-private {  background-image: url(data:image/gif;base64,R0lGODlhCwASAJEAAP///zMzM/8zMwAAACH5BAAAAAAALAAAAAALABIAAAIdhI+py30RWhCRzfrgpGhTwRnahnVhclmlw7auUwAAOw==) !important;}
.dumphper-protected {  background-image: url(data:image/gif;base64,R0lGODlhCwASAJEAAP/eADMzM////wAAACH5BAAAAAAALAAAAAALABIAAAIdlI+py30RWgCRzfrgpGhTwBnahnVhclmlw7auUwAAOw==) !important;}
.dumphper-public {  background-image: url(data:image/gif;base64,R0lGODlhCwASAJEAAADdADMzM////wAAACH5BAAAAAAALAAAAAALABIAAAIdlI+py30RWgCRzfrgpGhTwBnahnVhclmlw7auUwAAOw==) !important;}
.dumphper-private-static {  background-image: url(data:image/gif;base64,R0lGODlhCwASAJEAAP///zMzM/8zMwAAACH5BAAAAAAALAAAAAALABIAAAIdhI+pyw0BY0BBiHqpxfPI6CQfpF1byVFjyLbuUgAAOw==) !important;}
.dumphper-protected-static {  background-image: url(data:image/gif;base64,R0lGODlhCwASAJEAAP///zMzM//eAAAAACH5BAAAAAAALAAAAAALABIAAAIdhI+pyw0BY0BBiHqpxfPI6CQfpF1byVFjyLbuUgAAOw==) !important;}
.dumphper-public-static {  background-image: url(data:image/gif;base64,R0lGODlhCwASAJEAAP///zMzMwDdAAAAACH5BAAAAAAALAAAAAALABIAAAIdhI+pyw0BY0BBiHqpxfPI6CQfpF1byVFjyLbuUgAAOw==) !important;}
.dumphper-toggler { color: #fff !important; display: block; background: url(data:image/gif;base64,R0lGODlhCgAKAJEAAOfn5zMzM////wAAACH5BAEAAAIALAAAAAAKAAoAAAISlI+pe+HvRAC0UmdtkPCxDyIFADs=) 1px 3px no-repeat !important; padding-left: 14px !important; text-decoration: none !important; cursor: pointer !important;}
.dumphper-closed .dumphper-toggler { background-image: url(data:image/gif;base64,R0lGODlhCgAKAJEAAOfn5zMzM////wAAACH5BAEAAAIALAAAAAAKAAoAAAIZlBVxGwC6kIMmtZek2/PyiSQdpZEVGIVLAQA7) !important; }
.dumphper-closed .dumphper-table, .dumphper-closed .dumphper-class-def { display: none !important; }
</style>
<script type="text/javascript">
function dumphper_toggle(obj)
{
	obj.parentNode.parentNode.className = (obj.parentNode.parentNode.className == 'dumphper-container' ? 'dumphper-container dumphper-closed' : 'dumphper-container');
}
function dumphper_show(source_obj)
{
	obj = document.getElementById(source_obj.href.match(/[^#]+$/));
	while(obj = obj.parentNode)
		if (obj.className == 'dumphper-container dumphper-closed')
			obj.className = 'dumphper-container';
}
</script><?php
	}
}

/** Comment if you don't need this **/
//function dump(&$var) { Dumphper::dump($var); }