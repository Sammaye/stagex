<?php

namespace glue\components\purify;

/**
 * This class is based off the awesome plugin made for Yii: http://www.yiiframework.com/extension/input/
 * 90% of this classes code goes to its @author http://www.yiiframework.com/user/7442/ (twisted1919)
 */
class purify extends \glue\Component
{
    // flag marked true when the $_POST has been globally cleaned.
    protected $cleanPostCompleted   = false;

    // flag marked true when $_GET has been globally cleaned.
    protected $cleanGetCompleted    = false;

    // holds the default cleaning method for global filtering.
    protected $defaultCleanMethod   = 'stripClean';

    // the Codeigniter Xss Filter object.
    protected $CI_Security;

    // array() holding the original $_POST.
    protected $originalPost = array();

    // array() holding the original $_GET
    protected $originalGet  = array();

    // HtmlPurifier object.
    protected $purifier;

    // determines if $_POST should be cleaned globally.
    public $_cleanPost   = true;

    // determines if $_GET should be cleaned globally
    public $_cleanGet    = true;

    // which methods will be used when doing the cleaning.
    public $_cleanMethod = 'stripClean';

    public $_HTMLPurifierOptions = array(
	    /*'Attr.AllowedRel'        =>  array('noindex','nofollow'),
	    'Attr.DefaultImageAlt'   =>  NULL,
	    'Core.ColorKeywords'     =>  array(
	        'maroon'    => '#800000',
	        'red'       => '#FF0000',
	        'orange'    => '#FFA500',
	        'yellow'    => '#FFFF00',
	        'olive'     => '#808000',
	        'purple'    => '#800080',
	        'fuchsia'   => '#FF00FF',
	        'white'     => '#FFFFFF',
	        'lime'      => '#00FF00',
	        'green'     => '#008000',
	        'navy'      => '#000080',
	        'blue'      => '#0000FF',
	        'aqua'      => '#00FFFF',
	        'teal'      => '#008080',
	        'black'     => '#000000',
	        'silver'    => '#C0C0C0',
	        'gray'      => '#808080',
	    ),
	    'Core.Encoding'          =>  Yii::app()->charset,
	    'Core.EscapeInvalidTags' =>  FALSE,
	    'HTML.AllowedElements'   =>  array(
	        'a','b','em','small','strong','del','q','img','span','ul','ol','li','h1','h2','h3','h4','h5','h6'
	    ),
	    'HTML.AllowedAttributes' =>  array(
	        'href','rel','target','src', 'style',
	    ),
	    */
	    'HTML.Doctype'          =>  'XHTML 1.0 Transitional',
	    'URI.AllowedSchemes'    =>  array(
	        'http'      => true,
	        'https'     => true,
	        'mailto'    => true,
	        'ftp'       => true,
	        'nntp'      => true,
	        'news'      => true,
	    ),
	    'URI.Base'=>NULL,
    );


    /**
     * purify::init()
     *
     * @return
     */
    public function init()
    {
    	// TODO add the ability to preload this and add behaviour attachements to certain things.
//        $this->originalPost=$_POST;
//        $this->originalGet=$_GET;
//
//        parent::init();
//        Yii::app()->attachEventHandler('onBeginRequest', array($this, 'cleanGlobals'));
    }

    /**
     * purify::purify()
     *
     * @param mixed $str
     * @return
     */
    public function purify($str)
    {
        if(is_array($str))
        {
            foreach($str AS $k=>$v)
                $str[$k]=$this->purify($v);
            return $str;
        }
        return $this->getHtmlPurifier()->purify($str);
    }

    /**
     * purify::xssClean()
     *
     * @param mixed $str
     * @param bool $isImage
     * @return
     */
    public function xssClean($str, $isImage=false)
    {
        return $this->getCISecurity()->xss_clean($str, $isImage);
    }

    /**
     * purify::stripTags()
     *
     * @param mixed $str
     * @param bool $encode
     * @return
     */
    public function stripTags($str, $encode=false)
	{
        if(is_array($str))
        {
            foreach($str AS $k=>$v)
                $str[$k]=$this->stripTags($v, $encode);
            return $str;
        }
        $str=trim(strip_tags($str));

        if($encode)
            $str=$this->encode($str);
        return $str;
	}

    /**
     * purify::stripCleanEncode()
     *
     * @param mixed $str
     * @return
     */
    public function stripCleanEncode($str)
    {
        if(is_array($str))
        {
            foreach($str AS $k=>$v)
                $str[$k]=$this->stripCleanEncode($v);
            return $str;
        }
        return $this->encode($this->stripClean($str));
    }

    /**
     * purify::cleanEncode()
     *
     * @param mixed $str
     * @return
     */
    public function cleanEncode($str)
    {
        return $this->encode($this->xssClean($str));
    }

    /**
     * purify::stripClean()
     *
     * @param mixed $str
     * @return
     */
    public function stripClean($str)
    {
        return $this->xssClean($this->stripTags($str));
    }

    /**
     * purify::encode()
     *
     * @param mixed $str
     * @return
     */
    public function encode($str)
    {
        if(is_array($str))
        {
            foreach($str AS $k=>$v)
                $str[$k]=$this->encode($v);
            return $str;
        }
        return html::encode($str);
    }

    /**
     * purify::decode()
     *
     * @param mixed $str
     * @return
     */
    public function decode($str)
    {
        if(is_array($str))
        {
            foreach($str AS $k=>$v)
                $str[$k]=$this->decode($v);
            return $str;
        }
        return html::decode($str);
    }

    /**
     * purify::stripEncode()
     *
     * @param mixed $str
     * @return
     */
    public function stripEncode($str)
    {
        return $this->stripTags($str, true);
    }

    /**
     * purify::get()
     *
     * @param mixed $key
     * @param string $defaultValue
     * @param bool $clean
     * @return
     */
    public function get($key=null, $defaultValue=null, $clean=true)
    {
        $cleanMethod = $this->getCleanMethod();
        if(empty($key) && empty($defaultValue))
        {
            if($clean===true && $this->cleanGetCompleted===false)
                return $this->$cleanMethod($_GET);
            return $_GET;
        }
        $value=glue::http()->param($key, $defaultValue);
        if($clean===true && $this->cleanGetCompleted===false && !empty($value))
            return $this->$cleanMethod($value);
        return $value;
    }

    /**
     * purify::getQuery()
     *
     * @param mixed $key
     * @param string $defaultValue
     * @param bool $clean
     * @return
     */
     public function getQuery($key=null, $defaultValue=null, $clean=true)
     {
        return $this->get($key, $defaultValue, $clean);
     }

    /**
     * purify::post()
     *
     * @param mixed $key
     * @param string $defaultValue
     * @param bool $clean
     * @return
     */
    public function post($key=null, $defaultValue=null, $clean=true)
    {
        $cleanMethod = $this->getCleanMethod();
        if(empty($key) && empty($defaultValue))
        {
            if($clean===true && $this->cleanPostCompleted===false)
                return $this->$cleanMethod($_POST);
            return $_POST;
        }
        $value = glue::http()->param($key, $defaultValue);
        if($clean===true && $this->cleanPostCompleted===false && !empty($value))
            return $this->$cleanMethod($value);
        return $value;
    }

    /**
     * purify::getPost()
     *
     * @param mixed $key
     * @param string $defaultValue
     * @param bool $clean
     * @return
     */
    public function getPost($key, $defaultValue=null, $clean=true)
    {
        return $this->post($key, $defaultValue, $clean);
    }

    /**
     * purify::sanitizeFilename()
     *
     * @param mixed $file
     * @return
     */
    public function sanitizeFilename($file)
    {
        return $this->getCISecurity()->sanitize_filename($file);
    }

    /**
     * purify::cleanGlobals()
     *
     * @return
     */
    protected function cleanGlobals()
    {
        $cleanMethod = $this->getCleanMethod();

        if($this->getCleanPost()===true && $this->cleanPostCompleted===false && !empty($_POST))
        {
            $_POST=$this->post();
            $this->cleanPostCompleted=true;
        }
        if($this->getCleanGet()===true && $this->cleanGetCompleted===false && !empty($_GET))
        {
            $_GET=$this->get();
            $this->cleanGetCompleted=true;
        }
    }

    /**
     * purify::setCleanPost()
     *
     * @param mixed $str
     * @return
     */
    public function setCleanPost($str)
    {
        $this->_cleanPost=(bool)$str;
    }

    /**
     * purify::getCleanPost()
     *
     * @return
     */
    public function getCleanPost()
    {
        return $this->_cleanPost;
    }

    /**
     * purify::setCleanGet()
     *
     * @param mixed $str
     * @return
     */
    public function setCleanGet($str)
    {
        $this->_cleanGet=(bool)$str;
    }

    /**
     * purify::getCleanGet()
     *
     * @return
     */
    public function getCleanGet()
    {
        return $this->_cleanGet;
    }

    /**
     * purify::setCleanMethod()
     *
     * @param mixed $str
     * @return
     */
    public function setCleanMethod($str)
    {
        if(!method_exists($this, $str))
            $str=$this->defaultCleanMethod;
        $this->_cleanMethod=$str;
    }

    /**
     * purify::getCleanMethod()
     *
     * @return
     */
    public function getCleanMethod()
    {
        return $this->_cleanMethod;
    }

    /**
     * purify::getOriginalPost()
     *
     * @param mixed $key
     * @param string $defaultValue
     * @return
     */
    public function getOriginalPost($key=null, $defaultValue=null)
    {
        if(empty($key))
            return $this->originalPost;
        return isset($this->originalPost[$key])?$this->originalPost[$key]:$defaultValue;
    }

    /**
     * purify::getOriginalGet()
     *
     * @param mixed $key
     * @param string $defaultValue
     * @return
     */
    public function getOriginalGet($key=null, $defaultValue=null)
    {
        if(empty($key))
            return $this->originalGet;
        return isset($this->originalGet[$key])?$this->originalGet[$key]:$defaultValue;
    }

    /**
     * purify::getCISecurity()
     *
     * @return
     */
    private function getCISecurity()
    {
        if($this->CI_Security!==null)
            return $this->CI_Security;
        glue::import('glue/plugins/purifier/CI_Security.php');
        $this->CI_Security=new CI_Security;
        return $this->CI_Security;
    }

    /**
     * purify::getHtmlPurifier()
     *
     * @return
     */
    private function getHtmlPurifier()
    {
    	if(!class_exists('HTMLPurifier_Bootstrap',false) && !$this->purifier){
			require_once(str_replace('/', DIRECTORY_SEPARATOR, ROOT.'/glue/plugins/purifier/HTMLPurifier.standalone.php'));
			HTMLPurifier_Bootstrap::registerAutoload();
			$this->purifier=new HTMLPurifier($this->_HTMLPurifierOptions);

			// TODO Change this to go to a runtime folder to hold the cache in /application/runtime
			//$purifier->config->set('Cache.SerializerPath',Yii::app()->getRuntimePath());
    	}
    	return $this->purifier;
    }
}