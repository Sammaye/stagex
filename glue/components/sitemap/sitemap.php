<?php
namespace glue\components\sitemap;

use glue;

class sitemap extends \glue\Component{

	public $path = '/';
	public $sitemap_name = 'site_map.xml';
	public $index_name = 'sitemaps.xml';

	private $sitemap_path;
	private $index_path;

	public function __construct(){
		$this->sitemap_path = glue::getpath('@app').$this->path.$this->sitemap_name;
		$this->index_path = glue::getpath('@app').$this->path.$this->index_name;
	}

	public function getSitemap(){

		if(!file_exists($this->sitemap_path)){
			$xml=<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd"
         xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
</urlset>
XML;

			touch($this->sitemap_path); // Add the sitemap if it does not exist
			file_put_contents($this->sitemap_name, $xml);

			return new \SimpleXMLElement($xml);
		}else
			return new \SimpleXMLElement($this->sitemap_path, 0, true);
	}

	public function getIndex(){

		if(!file_exists($this->index_path)){
			$xml_index=<<<XMLINDEX
<?xml version='1.0' encoding='UTF-8'?>
<sitemapindex xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/siteindex.xsd"
         xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
</sitemapindex>
XMLINDEX;

			touch($this->index_path); // Add the sitemap if it does not exist
			file_put_contents($this->index_path, $xml);

			return new \SimpleXMLElement($xml);
		}else
			return new \SimpleXMLElement($this->index_path, 0, true);

	}

	// Will add sitemap to an index
	public function addToIndex($sitemap){}

	public function remove(){
		unlink($this->sitemap_path);
	}

	public function addUrl($url, $changefreq = 'hourly', $priority = '0.5', $lastmod = null){

		$sitemap = $this->getSitemap();

		$url = $sitemap->addChild('url');
		$url->addChild('loc', $url);
		$url->addChild('changefreq', $changefreq);
		$url->addChild('priority', $priority);
		$url->addChild('lastmod', $this->getDatetime());
		$sitemap->saveXML($this->sitemap_path);
	}

	public function getDatetime($val = null){
		if($val===null)
			return date(DATE_W3C);
		else if (is_int($val))
			return date(DATE_W3C, $val);
	}
}