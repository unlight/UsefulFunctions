<?php
/**
 *
 * PHP CSS Sprite generator
 *
 * This class will generate both an image, and a css class to use.
 * This class also has no comments. yay! This code is BSD licensed.
 * If you are nice you could credit me for using it. If you stumbled
 * across this code and have no idea how to use it, visit my site 
 * and it has examples and stuff in the download package.
 *
 * This library requires montage, part of imagemagick!
 *
 * Devin Smith            www.devin-smith.com            2009.06.18
 *
 */
 
 // Fixed by S.


class CssSprite {
	private $_file;
	private $_height;
	private $_width;
	private $_x;
	private $_y;
	private $_params;

	public function setFile($v) {
		$this->_file = $v;
		return $this;
	}

	public function getFile() {
		return $this->_file;
	}

	public function setHeight($v) {
		$this->_height = $v;
		return $this;
	}

	public function getHeight() {
		return $this->_height;
	}

	public function setWidth($v) {
		$this->_width = $v;
		return $this;
	}

	public function getWidth() {
		return $this->_width;
	}	

	public function setY($v) {
		$this->_y = $v;
		return $this;
	}

	public function getY() {
		return $this->_y;
	}

	public function setX($v) {
		$this->_x = $v;
		return $this;
	}

	public function getX() {
		return $this->_x;
	}

	public function setParams($v) {
		$this->_params = $v;
		return $this;
	}

	public function getParams() {
		return $this->_params;
	}

}

class CssSprites {
	private $_sprites;

	public function all() {
		return $this->_sprites;
	}
	
	public function get($k) {
		if (isset($this->_sprites[$k])) {
			return $this->_sprites[$k];
		} else {
			return $this->_sprites[$k] = new CssSprite;
		}
	}
	
	public function setSprite($k,$v) {
		return $this->_sprites[$k] = $v;
	}
	
	public function count() {
		return count($this->_sprites);
	}
}

class CssSpriteMap {

	private $_outPath = './images/cache/';
	private $_outUrl = '/images/cache/';
	private $_outName;
	private $_images = array();
	private $_outExt = '.png';
	private $_permissions = '0755';
	private $_map;
	private $_imPath = '/usr/local/bin/';
	//private $_imPath = 'd:/Program Files/ImageMagick-6.6.4-Q16/';
	private $_spacing = 0;
	private $_forceRefresh = false;
	private $_inPath = './';
	private $_css;
	private $_cssPrefix = 'csssprite';
	private $_OutOptions = '';


	public function getImg($image) {
		if ($this->getMap()) {
			return $this->getMap()->get($image);
		} else {
			return false;
		}
	}

	public function createClass($name = 'sprite') {
		$css = '';

		$css .= '.'.$this->getCssPrefix().' {'
			. 'background: url('.$this->getOutUrl().$this->getOutName().');'
			. '}';
		if ($this->getMap()->all()) {
			foreach ($this->getMap()->all() as $key => $image) {
				$params = $image->getParams();
				$css .= ' .'.$this->getCssPrefix().$key.' {';
	
	
				if ($params && count($params['style']) > 0 ) {
					foreach ($params['style'] as $key => $value) {
						$css .= $key.': '.$value.';';
					}
					if (!$params['style']['width']) {
						$css .= 'width: '.$image->getWidth().'px;';
					}
					if (!$params['style']['height']) {
						$css .= 'height: '.$image->getHeight().'px;';
					}
				} else {
					$css .= 'width: '.$image->getWidth().'px;'
						. 'height: '.$image->getHeight().'px;';
				}
	
				$css .= 'background-position: '.$image->getX().'px -'.$image->getY().'px;'
					. '}';			
	
			}
		}

		$this->setCss($css);
		return $this;
	}

	public function create() {
		return $this->createSprite()
				->createClass();
	}


	public function createSprite() {

		$images = $this->getImages();
		$this->setOutName(md5(print_r($this->getImages(),1)).$this->getOutExt());
		$this->setMap(new CssSprites());
		
		$useableImages = '';
		$currentPos = 0;

		foreach ($images as $key => $image) {

			if (file_exists($this->getInPath().$image['file'])) {

				$params = array();
				foreach ($image as $paramKey => $paramValue) {
					if ($paramKey != 'file') {
						$params[$paramKey] = $paramValue;
					}
				}

				$imginfo = getimagesize($this->getInPath().$image['file']);
				$imgCount = 0;
				$this->getMap()->get($key)
					->setFile($this->getInPath().$image['file'])
					->setHeight($imginfo[1])
					->setWidth($imginfo[0])
					->setX(0)
					->setParams($params)
					->setY($currentPos);

				$useableImages .= escapeshellarg($this->getInPath().$image['file']).' ';
				$currentPos += $imginfo[1] + $this->getSpacing();
			}
		}

		if ($this->getForceRefresh() || (!file_exists($this->getOutPath().$this->getOutName()) && $useableImages)){
		 	$cmd = realpath($this->getImPath()) . '/montage ' . $useableImages . ' -mode Concatenate -tile 1x -geometry +0+' . $this->getSpacing(). ' '.$this->OutOptions().' ' . $this->getOutPath().$this->getOutName();
			$out = $rar = Null;
			exec($cmd,$out,$rar);
			if ($rar !== 0) trigger_error('Montage failed.', E_USER_ERROR);
			@chmod($this->getOutPath().$this->getOutName(), $this->getPermissions());
		}

		return $this;	

	}
	
	// Fixed by S.
	public function OutOptions($Value = NULL) {
		if ($Value === NULL) return $this->_OutOptions;
		$this->_OutOptions = $Value;
		return $this;
	}
	

	public function setOutPath($v) {
		$this->_outPath = $v;
		return $this;
	}

	public function getOutPath() {
		return $this->_outPath;
	}

	public function setOutName($v) {
		$this->_outName = $v;
		return $this;
	}

	public function getOutName() {
		return $this->_outName;
	}

	public function setImages($v) {
		$this->_images = $v;
		return $this;
	}

	public function getImages() {
		return $this->_images;
	}

	public function setOutExt($v) {
		$this->_outExt = $v;
		return $this;
	}

	public function getOutExt() {
		return $this->_outExt;
	}

	public function setPermissions($v) {
		$this->_permissions = $v;
		return $this;
	}

	public function getPermissions() {
		return $this->_permissions;
	}

	public function setMap($v) {
		$this->_map = $v;
		return $this;
	}

	public function getMap() {
		return $this->_map;
	}

	public function setImPath($v) {
		$this->_imPath = $v;
		return $this;
	}

	public function getImPath() {
		return $this->_imPath;
	}

	public function setSpacing($v) {
		$this->_spacing = $v;
		return $this;
	}

	public function getSpacing() {
		return $this->_spacing;
	}

	public function setForceRefresh($v) {
		$this->_forceRefresh = $v;
		return $this;
	}

	public function getForceRefresh() {
		return $this->_forceRefresh;
	}

	public function setInPath($v) {
		$this->_inPath = $v;
		return $this;
	}

	public function getInPath() {
		return $this->_inPath;
	}

	public function setCss($v) {
		$this->_css = $v;
		return $this;
	}

	public function getCss() {
		return $this->_css;
	}

	public function setCssPrefix($v) {
		$this->_cssPrefix = $v;
		return $this;
	}

	public function getCssPrefix() {
		return $this->_cssPrefix;
	}

	public function setOutUrl($v) {
		$this->_outUrl = $v;
		return $this;
	}

	public function getOutUrl() {
		return $this->_outUrl;
	}
}



