<?php

namespace glue\widgets;

use glue;

/**
 * GListView Widget
 *
 * This is a more relaxed version of a pure grid view.
 * It allows for custom view files for each item in the table giving a more fluid output than grids.
 *
 * @author Sam Millman
 */
class ListView extends \glue\Widget{

	public $id; // The id of the widget, mostly used for AJAX and JQuery stuff

	public $cursor;

	/**
	 * @example array('upload_date' => 'Upload Date', 'duration', 'user_id' => array('sort' => 1 //ASC / -1 //DESC, 'label' => 'User'))
	 */
	public $sortableAttributes;

	/**
	 * @example array('upload_date' => -1)
	 */
	public $sort; // This will either be current sort from $_GET or default sort

	public $enableSorting = true;
	public $enablePagination = true;

	public $enableAjaxPagination = false;
	public $ajaxData; // Additional Ajax GET Data
	public $ajaxUrl; // The URL for Ajax Paging

	/**
	 * Additional data to be passed to the view when rendering
	 */
	public $data;

	public $template = "{items}{pager}";
	public $itemView;

	public $page = 1;
	public $pageSize = 20;
	public $maxPage; // Max number of available pages

	public $sorterCssClass;
	public $pagerCssClass;

	protected $itemCount = 0;
	protected $pageItemCount = 0; // This is the amount of items really on the page

	/**
	 * These two are normally taken from the $_GET and won't be set on fresh run of this widget
	 */
	protected $currentSortAttribute;
	protected $currentSortOrder;

	function pages(){
		return $this->maxPage;
	}

	function init(){
		$this->pageSize = glue::http()->param('pagesize') > 0 ? glue::http()->param('pagesize') : 20;
		$this->page = glue::http()->param('page') > 0 ? glue::http()->param('page') : 1;

		$this->currentSortAttribute = glue::http()->param('sorton');
		$this->currentSortOrder = glue::http()->param('orderby');
	}

	function sortFromParams(){

		if($this->currentSortAttribute === null)
			return; // Do not process sort if none was provided

		if(!isset($this->sortableAttributes[$this->currentSortAttribute]))
			return; // Do not process sort

		$sortableAttribute = $this->sortableAttributes[$this->currentSortAttribute];

		if(is_array($sortableAttribute) && isset($sortableAttribute['sort'])){
			// Then check if this sort is allowed
			$order = $this->currentSortOrder != $sortableAttribute['sort'] ? $sortableAttribute['sort'] : $this->currentSortOrder;
		}else{
			$order = $this->currentSortOrder == '-1' ? -1 : 1; // By default make it ASC
		}

		$this->cursor->sort(array($this->currentSortAttribute => $order));
	}

	function render(){

		if(!$this->cursor instanceof \glue\db\Cursor)
			trigger_error("You must supply a Cursor for the cursor param of the ListView widget");

		$this->itemCount = $this->cursor->count();

		if($this->enableSorting){

			if($this->currentSortAttribute!==null){
				$this->sortFromParams();
			}elseif(is_array($this->sort)){
				foreach($this->sort as $field => $sort){
					$this->currentSortAttribute = $field;
					$this->currentSortOrder = $sort;
					break; // @todo add multifield support
				}
				$this->cursor->sort($this->sort);
			}
			// ELSE do not sort
		}

		// Get the current page
		if($this->enablePagination){
			// Double check current page and make amendmants if needed
			$this->maxPage = ceil($this->itemCount / $this->pageSize) < 1 ? 1 : ceil(($this->itemCount) / $this->pageSize);
			if($this->page < 0 || $this->maxPage < 0){
				$this->maxPage = 1;
				$this->page = 1;
			}

			if($this->page > $this->maxPage) $this->page = $this->maxPage;
			$this->cursor->skip(($this->page-1)*$this->pageSize)->limit($this->pageSize);

			$pager = $this->__renderPager();
			$html = preg_replace("/{pager}/", $pager, $this->template);
		}

		ob_start();
			$this->__renderItems();
			$items = ob_get_contents();
		ob_end_clean();

		$html = preg_replace("/{items}/", $items, $html);
		echo $html;
	}

 	function __renderPager(){

 		//$this->maxPage = 10;

		$start = $this->page - 5 > 0 ? $this->page - 5 : 1;
		$end = $this->page + 5 <= $this->maxPage ? $this->page + 5 : $this->maxPage;
		$ret = "";

		$ret .= "<div class='ListView_Pager {$this->pagerCssClass}'>";

	    if($this->page != 1 && $this->maxPage > 1) {
	    	if($this->enableAjaxPagination){
	        	$ret .= '<div class="control"><a href="#page_'.($this->page-1).'">Previous</a></div>';
	    	}else{
	        	$ret .= '<div class="control"><a href="'.$this->getUrl(array('page' => $this->page-1)).'">Previous</a></div>';
	    	}
	    }

	    if($this->maxPage > 1){
	    	$ret .= '<ul>';
		    for ($i = $start; $i <= $end && $i <= $this->maxPage; $i++){

		        if($i==$this->page) {
		        	$ret .= '<li><div class="active" data-page="'.$i.'" style="margin-right:6px;"><span>'.$i.'</span></div></li>';
		        } else {
		        	if($this->enableAjaxPagination){
		            	$ret .= '<li><a style="margin-right:6px;" href="#page_'.($i).'"><span>'.$i.'</span></a></li>';
		        	}else{
		            	$ret .= '<li><a style="margin-right:6px;" href="'.$this->getUrl(array('page' => $i)).'"><span>'.$i.'</span></a></li>';
		        	}
		        }
		    }
		    $ret .= '</ul>';
	    }

	    if($this->page < $this->maxPage) {
	    	if($this->enableAjaxPagination){
				$ret .= '<div class="control"><a href="#page_'.($this->page+1).'">Next</a></div>';
	    	}else{
				$ret .= '<div class="control"><a href="'.$this->getUrl(array('page' => $this->page+1)).'">Next</a></div>';
	    	}
	    }

	    $ret .= "</div>";
	    return $ret;
	}

	function __renderItems(){
		$i = 0;

		if($this->data){
			foreach($this->data as $k=>$v){
				$$k = $v;
			}
		}

		foreach($this->cursor as $_id => $item){
			if(is_string($this->itemView)){ // Is it a file location?
				ob_start();
					include $this->getView($this->itemView);
					$item = ob_get_contents();
				ob_end_clean();
				echo $item;
			}
			$i++;
		}
	}

	function getUrl($morph = array()){
		return glue::http()->getUrl(array_merge($this->data, array_merge(
			array(
				//"mode"=>urlencode($this->mode),
				"pagesize"=>$this->pageSize,
				"page"=>$this->page,
				"sorton"=>$this->currentSortAttribute,
				"orderby"=>$this->currentSortOrder
			), $morph
		)));
	}

	function getView($path){

		$path = strlen(pathinfo($path, PATHINFO_EXTENSION)) <= 0 ? $path.'.php' : $path;

		if(mb_substr($path, 0, 1) == '/'){

			// Then this should go from doc root
			return str_replace('/', DIRECTORY_SEPARATOR, glue::getPath('@app').$path);

		}elseif(strpos($path, '/')!==false){

			// Then this should go from views root (/application/views) because we have something like user/edit.php
			return str_replace('/', DIRECTORY_SEPARATOR, glue::getPath('@app').'/views/'.$path);

		}else{

			// Then lets attempt to get the cwd from the controller. If the controller is not set we use siteController as default. This can occur for cronjobs
			return str_replace('/', DIRECTORY_SEPARATOR, glue::getPath('@app').'/views/'.str_replace('controller', '',
					strtolower(isset(glue::$action['controller']) ? glue::$action['controller'] : 'indexController')).'/'.$path);
		}
	}

	function end(){}
}