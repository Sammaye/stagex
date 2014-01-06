<?php

namespace glue\widgets;

use Glue;
use \glue\Widget;

class Pagination extends Widget
{	
	public $pageParam = 'page';
	public $sizeParam = 'pagesize';
	
	public $params = array(); // Additional URL vars
		
	public $itemCount;

	public $enableAjaxPagination = false;
	public $cssClass = '';
	
	public $validatePage = false;
	
	public $previousCaption = 'Previous';
	public $nextCaption = 'Next';
	
	public $pageRange = 5;
	
	private $_page;
	private $_pageSize;
	private $_pageCount;
	
	public function getPageSize()
	{
		if($this->_pageSize === null){
			$this->_pageSize = glue::http()->param($this->sizeParam);
			if($this->_pageSize <= 0){
				$this->_pageSize = 20;
			}
		}
	}
	
	public function setPageSize($size)
	{
		$this->_pageSize = $size;
	}
	
	public function getPageCount($refresh = false)
	{
		if($refresh || $this->_pageCount === null){
			$this->_pageCount = (int)$this->_itemCount / $this->pageSize;
		}
		return $this->_pageCount;
	}
	
	public function setPageCount($count)
	{
		$this->_pageCount = $count;
	}
	
	public function getPage($refresh = false)
	{
		if($refresh || $this->_page === null){
			if($page = glue::http()->param($this->pageParam)){
				$this->_page = $page;
				if($this->validatePage && $page > $this->getPageCount()){
						$this->_page = $this->getPageCount();
				}
				if($page <= 0){
					$this->_page = 1;
				}
			}else{
				$this->_page = 1;
			}
		}
		return $this->_page;
	}
	
	public function setPage($page)
	{
		$this->_page = $page;
	}
	
	public function render()
	{
		if($this->getPageSize() < 1)
			return; // Infinite
		
		$page = $this->getPage();
		$pageCount = $this->getPageCount();
		
		$start = $page - $this->pageRange > 0 ? $page - $this->pageRange : 1;
		$end = ($page + $this->pageRange) <= $pageCount ? $page + $this->pageRange : $pageCount;
		$ret = "";
		
		$ret .= "<div class='pagination_widget {$this->cssClass}'>";
		
		if($pageCount > 1){
			$ret .= '<ul class="pagination">';
			if($page != 1 && $pageCount > 1) {
				if($this->enableAjaxPagination){
					$ret .= '<li class="control"><a href="#page_'.($page-1).'">'.$this->previousCaption.'</a></li>';
				}else{
					$ret .= '<li class="control"><a href="'.$this->createUrl(array($this->pageParam => $page-1)).'">'.$this->previousCaption.'</a></li>';
				}
			}
		
			for ($i = $start; $i <= $end && $i <= $pageCount; $i++){
				if($i==$page) {
					if($this->enableAjaxPagination){
						$ret .= '<li class="active" data-page="'.$i.'"><a href="#page_'.$i.'">'.$i.'</a></li>';
					}else
						$ret .= '<li class="active" data-page="'.$i.'"><a href="'.$this->createUrl(array($this->pageParam => $i)).'">'.$i.'</a></li>';
				} else {
					if($this->enableAjaxPagination){
						$ret .= '<li><a href="#page_'.($i).'">'.$i.'</a></li>';
					}else{
						$ret .= '<li><a href="'.$this->createUrl(array($this->pageParam => $i)).'">'.$i.'</a></li>';
					}
				}
			}
		
			if($page < $pageCount) {
				if($this->enableAjaxPagination){
					$ret .= '<li class="control"><a href="#page_'.($this->page+1).'">'.$this->nextCaption.'</a></li>';
				}else{
					$ret .= '<li class="control"><a href="'.$this->createUrl(array($this->pageParam => $this->page+1)).'">'.$this->nextCaption.'</a></li>';
				}
			}
			$ret .= '</ul>';
		}
		
		$ret .= "</div>";
		return $ret;		
	}
	
	function createUrl($morph = array())
	{
		return glue::http()->url(array_merge($this->params, array(
			$this->sizeParam => $this->getPageCount(),
			$this->pageParam =>$this->getPage(),
		), $morph));
	}	
	
	function getSkip()
	{
		if($this->getPageSize() > 1){
			return ($this->getPage() - 1) * $this->getPageSize();
		}
		return 0;
	}
	
	function getLimit()
	{
		if($this->getPageSize() > 1){
			return $this->getPageSize();
		}
		return null;
	}
} 