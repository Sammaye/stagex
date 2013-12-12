<?php

namespace glue\widgets;

class Pagination extends \glue\Widget{
	
	public $page=1;
	public $pageSize=20;
	public $totalItems;
	public $maxPage;
	
	public $enableAjaxPagination=false;
	public $pagerCssClass='';
	
	public function init(){
		if($this->totalItems>0)
			$this->maxPage=$this->totalItems/$this->pageSize;
		if($this->page>$this->maxPage)
			$this->page=$this->maxPage;
	}
	
	public function render(){
		
		//$this->maxPage = 10;
		
		$start = $this->page - 5 > 0 ? $this->page - 5 : 1;
		$end = $this->page + 5 <= $this->maxPage ? $this->page + 5 : $this->maxPage;
		$ret = "";
		
		$ret .= "<div class='ListView_Pager {$this->pagerCssClass}'>";
		
		if($this->maxPage > 1){
			$ret .= '<ul class="pagination">';
			if($this->page != 1 && $this->maxPage > 1) {
				if($this->enableAjaxPagination){
					$ret .= '<li class="control"><a href="#page_'.($this->page-1).'">Previous</a></li>';
				}else{
					$ret .= '<li class="control"><a href="'.$this->getUrl(array('page' => $this->page-1)).'">Previous</a></li>';
				}
			}
		
			for ($i = $start; $i <= $end && $i <= $this->maxPage; $i++){
				if($i==$this->page) {
					if($this->enableAjaxPagination){
						$ret .= '<li class="active" data-page="'.$i.'" style="margin-right:6px;"><a href="#page_'.$i.'">'.$i.'</a></li>';
					}else
						$ret .= '<li class="active" data-page="'.$i.'" style="margin-right:6px;"><a href="'.$this->getUrl(array('page' => $i)).'">'.$i.'</a></li>';
				} else {
					if($this->enableAjaxPagination){
						$ret .= '<li><a href="#page_'.($i).'">'.$i.'</a></li>';
					}else{
						$ret .= '<li><a href="'.$this->getUrl(array('page' => $i)).'">'.$i.'</a></li>';
					}
				}
			}
		
			if($this->page < $this->maxPage) {
				if($this->enableAjaxPagination){
					$ret .= '<li class="control"><a href="#page_'.($this->page+1).'">Next</a></li>';
				}else{
					$ret .= '<li class="control"><a href="'.$this->getUrl(array('page' => $this->page+1)).'">Next</a></li>';
				}
			}
			$ret .= '</ul>';
		}
		
		$ret .= "</div>";
		echo $ret;		
	}
}