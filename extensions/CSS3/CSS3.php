<?php
/**
 * Scaffold_Extension_CSS3
 *
 * Automatically expand CSS3 properties to include vendor specific variations
 * and provide workarounds for unsupported browsers where they exist.
 * Currently supports:
 *     background-color: rgba()
 *     border-radius
 *     box-shadow
 *     opacity
 *     text-shadow
 *     transition
 *
 * @package 		Scaffold
 * @author 			Ben Cates <ben.cates@gmail.com>
 */
class Scaffold_Extension_CSS3 extends Scaffold_Extension
{

	private $behaviorpath;

	private function html2rgb($color) {
		if ($color[0] == '#')
			$color = substr($color, 1);

		if (strlen($color) == 6)
			list($r, $g, $b) = array(
				$color[0].$color[1],
				$color[2].$color[3],
				$color[4].$color[5]
			);
		elseif (strlen($color) == 3)
			list($r, $g, $b) = array(
				$color[0].$color[0],
				$color[1].$color[1],
				$color[2].$color[2]
			);
		else return false;

		$r = hexdec($r); $g = hexdec($g); $b = hexdec($b);

		return array($r, $g, $b);
	}

	private function rgb2html($r, $g=-1, $b=-1) {
		if (is_array($r) && sizeof($r) == 3)
			list($r, $g, $b) = $r;

		$r = intval($r); $g = intval($g); $b = intval($b);

		$r = dechex($r<0?0:($r>255?255:$r));
		$g = dechex($g<0?0:($g>255?255:$g));
		$b = dechex($b<0?0:($b>255?255:$b));

		$color = (strlen($r) < 2?'0':'').$r;
		$color .= (strlen($g) < 2?'0':'').$g;
		$color .= (strlen($b) < 2?'0':'').$b;

		return '#'.$color;
	}

	private function xy2rs($x, $y) {
		$rotation = round( atan2(-$y,$x) * 180/pi() );
		$strength = round( sqrt($x*$x) + sqrt($y*$y) );
		return array($rotation, $strength);
	}

	/**
	 * Registers the supported properties
	 * @access public
	 * @param $properties Scaffold_Extension_Properties
	 * @return array
	 */
	public function register_property($properties) {
		global $system;
		$this->behaviorpath = $system . 'extensions/CSS3/behaviors/';
		$properties->register('background-color',array($this,'background_color'));
		$properties->register('border-radius',array($this,'border_radius'));
		$properties->register('box-shadow',array($this,'box_shadow'));
		$properties->register('opacity',array($this,'opacity'));
		$properties->register('text_shadow',array($this,'text_shadow'));
		$properties->register('transition',array($this,'transition'));
	}

	/**
	 * @access public
	 * @param $source
	 * @return string
	 */
	public function initialize($source,$scaffold) {
		$this->source = $source;
	}

	/**
	 * Enables rgba backgrounds in IE
	 *
	 * Uses a fliter to emulate rgba backgrounds in IE.
	 *
	 * @access public
	 * @param $url
	 * @return string
	 */
	public function background_color($value) {
		$regexp = '/rgba\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*,\s*([\d\.]+)\s*\)/';
		if (preg_match($regexp,$value,$match)) {

			list(,$r,$g,$b,$a) = $match;
			$hex_color = $this->rgb2html($r,$g,$b);

			$hex_a = dechex(255*floatval($a));
			$hex_a = (strlen($hex_a) < 2?'0':'').$hex_a;
			$ms_color = '#' . $hex_a . substr($hex_color,1);

			$css = "background-color: $hex_color;"
				. "background-color: rgba($r, $g, $b, $a);"
				. "filter: progid:DXImageTransform.Microsoft.gradient("
					. "startColorStr='$ms_color',EndColorStr='$ms_color');";
		} else $css = "background-color: $value;";
		return $css;
	}

	/**
	 * Expands border-radius property
	 *
	 * Adds -moz- and -webkit- variants of border-radius.
	 * Uses ie-css3.htc for IE support.
	 *   (http://www.fetchak.com/ie-css3/)
	 *
	 * @access public
	 * @param $url
	 * @return string
	 */
	public function border_radius($value, $scaffold, $found) {
		$css = "-moz-border-radius:{$value};"
			. "-webkit-border-radius:{$value};"
			. "-khtml-border-radius:{$value};"
			. "border-radius:{$value};"
			. "behavior:url('{$this->behaviorpath}ie-css3.htc');";
		return $css;
	}

	/**
	 * Expands box-shadow property
	 *
	 * Adds -moz- and -webkit- variants of box-shadow.
	 * Uses ie-css3.htc for IE support.
	 *   (http://www.fetchak.com/ie-css3/)
	 *
	 * @access public
	 * @param $url
	 * @return string
	 */
	public function box_shadow($value) {
		$regexp = '/(\d+)px\s+(\d+)px\s+(\d+)px\s+(#[\da-fA-F]+)/';
		if(preg_match($regexp,$value,$match)) {
			list(,$x,$y,$blur,$color) = $match;
			list($rotation, $strength) = $this->xy2rs($x,$y);

			$css = "-moz-box-shadow:{$value};"
				. "-webkit-box-shadow:{$value};"
				. "box-shadow:{$value};";
			#$css .= "filter: progid:DXImageTransform.Microsoft.Shadow("
			#		. "color='$color', Direction=$rotation, Strength=$strength);";
			$css .= "behavior:url('{$this->behaviorpath}ie-css3.htc');";
		} else $css = '';
		return $css;
	}

	/**
	 * Enables opacity in IE
	 *
	 * Uses a fliter to set opacity in IE.
	 *
	 * @access public
	 * @param $url
	 * @return string
	 */
	public function opacity($value) {
		$regexp = '/\d?\.\d+/';
		if (preg_match($regexp,$value,$match)) {
			$opacity = $match[0];
			$ms_opacity = round(100*$opacity);

			$css = "opacity: $value;"
				. "filter: progid:DXImageTransform.Microsoft.Alpha("
					. "opacity=$ms_opacity);";
		} else $css = "opacity: $value;";
		return $css;
	}

	/**
	 * Expands text-shadow property
	 *
	 * Adds -moz- and -webkit- variants of text-shadow.
	 * Uses ie-css3.htc for IE support.
	 *   (http://www.fetchak.com/ie-css3/)
	 *
	 * @access public
	 * @param $url
	 * @return string
	 */
	public function text_shadow($value) {
		$css = "text-shadow: $value;";
		$css .= "behavior:url('{$this->behaviorpath}ie-css3.htc');";
		return $css;
	}

	/**
	 * Expands transition property
	 *
	 * Adds -moz- -o- and -webkit- variants of transition.
	 *
	 * @access public
	 * @param $url
	 * @return string
	 */
	public function transition($value) {
		$css = "-moz-transition: $value;"
			. "-o-transition: $value;"
			. "-webkit-transition: $value;"
			. "transition: $value;";
		return $css;
	}
}

