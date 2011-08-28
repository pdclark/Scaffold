<?php
/**
 * Scaffold_Extension_Gradient
 *
 * Easily background-gradient images in the CSS. You don't have to 
 * get the height and width of the file manually, it will calculate it for it 
 * and add the properties.
 * 
 * @package 		Scaffold
 * @author 			Anthony Short <anthonyshort@me.com>
 * @author			Paul Clark <support@pdclark.com>
 * @copyright 		2009-2010 Anthony Short. All rights reserved.
 * @license 		http://opensource.org/licenses/bsd-license.php  New BSD License
 * @link 			https://github.com/anthonyshort/csscaffold/master
 */
class Scaffold_Extension_Gradient extends Scaffold_Extension
{	
	/**
	 * List of created gradients and their locations
	 *
	 * @var array
	 */
	var $gradients = array();
	
	/**
	 * Registers the background-gradient property
	 * @access public
	 * @param $properties Scaffold_Extension_Properties
	 * @return array
	 */
	public function register_property($properties)
	{
		$properties->register('background-gradient',array($this,'background_gradient'));
	}
	
	/**
	 * @access public
	 * @param $source
	 * @return string
	 */
	public function initialize($source,$scaffold)
	{
		$this->source = $source;
	}
	
	/**
	 * Parses background-gradient properties
	 * @access public
	 * @param $url
	 * @return string
	 */
	public function background_gradient($params, $scaffold)
	{
		// Strip extra commas from stops
		if(preg_match_all('/\([^)]*?,[^)]*?\)/',$params, $matches))
		{
			foreach($matches as $key => $original)
			{
				$new = str_replace(',','#COMMA#',$original);
				$params = str_replace($original,$new,$params);
			}
		}
		
		$params = explode(',',$params);
		
		// Identify first 4 arguments
		foreach(array('dir','size','from','to') as $key => $name)
		{
			$$name = trim(str_replace('#COMMA#',',', array_shift($params) ));
		}

		// Remaining arguments are stops
		$stops = array();
		
		foreach($params as $stop)
		{
			$stop = preg_replace('/color\-stop\(|\)/','',$stop);
			$stop = explode('#COMMA#',$stop);
			$stops[] = array('position' => trim($stop[0]), 'color' => trim($stop[1]));
		}

		$from = preg_replace('/from\s*\(|\)/','',$from);
		$to = preg_replace('/to\s*\(|\)/','',$to);
		$size = str_replace('px','',$size);
		
		return $this->create_gradient($dir, $size, $from, $to, $stops, $scaffold);
	}
	
	public function create_gradient($direction, $size, $from, $to, $stops = false, $scaffold )
	{
		if (!class_exists('GradientGD'))
			include dirname(__FILE__).'/lib/gradientgd.php';
		
		$file = "{$direction}_{$size}_".str_replace('#','',$from)."_".str_replace('#','',$to).".png";

		if($direction == 'horizontal')
		{
			$height = 50;
			$width = $size;
			$repeat = 'y';
		}
		else
		{
			$height = $size;
			$width = 50;
			$repeat = 'x';
		}

		if(!$scaffold->cache->exists('gradients/'.$file)) 
		{
			$scaffold->cache->create('gradients');
			$file = $scaffold->cache->find('gradients') . '/' . $file;
			$gradient = new GradientGD($width,$height,$direction,$from,$to,$stops);
			$gradient->save($file);
		}
		
		$file = $scaffold->cache->find('gradients') . '/' . $file;
		
		$this->gradients[] = array
		(
			$direction,
			$size,
			$from,
			$to,
			$file
		);

		
		$file = substr( $file, strpos($file, '/wp-content') );

		$properties = "
			background-position: top left;
		    background-repeat: repeat-$repeat;
		    background-image: url('$file');
		";
		
		return $properties;

	}
}