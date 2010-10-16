<?php
/**
 * Scaffold_Extension_CSS3
 *
 * Automatically expand CSS3 properties to include vendor specific variations
 * and provide workarounds for unsupported browsers where they exist.
 *
 * @package 		Scaffold
 * @author 			Ben Cates <ben.cates@gmail.com>
 */
class Scaffold_Extension_CSS3 extends Scaffold_Extension
{

	private $behaviorpath;

	/**
	 * Registers the supported properties
	 * @access public
	 * @param $properties Scaffold_Extension_Properties
	 * @return array
	 */
	public function register_property($properties) {
		global $system;
		$this->behaviorpath = $system . 'extensions/CSS3/behaviors/';
		$properties->register('border-radius',array($this,'border_radius'));
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
	 * Expands border-radius property
	 *
	 * Adds -moz- -webkit- and -khtml- variants of border-radius.
	 * Uses Remiz Rahnas's border-radius.htc for IE support.
	 *   (http://code.google.com/p/curved-corner/)
	 *
	 * @access public
	 * @param $url
	 * @return string
	 */
	public function border_radius($value) {
		$css = "-moz-border-radius:{$value};"
			. "-webkit-border-radius:{$value};"
			. "-khtml-border-raius:{$value};"
			. "border-radius:{$value};"
			. "behavior:url('{$this->behaviorpath}border-radius.htc');";
		return $css;
	}

}

