<?php

namespace glue\widgets;

use glue;
use \glue\Widget;
use \glue\widgets\Pagination;

/**
 * listview Widget
 *
 * This is a more relaxed version of a pure grid view.
 * It allows for custom view files for each item in the table giving a more fluid output than grids.
 */
class ListView extends Widget
{
	public $cursor;

	/**
	 * @example array('upload_date' => -1)
	 */
	public $sort; // This will either be current sort from $_GET or default sort
	/**
	 * @example array('upload_date' => 'Upload Date', 'duration', 'user_id' => array('sort' => 1 //ASC / -1 //DESC, 'label' => 'User'))
	 */
	public $sortableAttributes;
	public $sortParam = 'sorton';
	public $orderParam = 'orderby';
	public $sorterCssClass;
	
	public $pagination = array();

	public $enableSorting = true;
	public $enablePagination = true;

	/**
	 * Additional data to be passed to the view when rendering
	 */
	public $data = array();

	public $template = '<div class="list_contents">{items}{pager}</div>';
	public $itemView;
	
	public $callback;

	protected $itemCount = 0;

	/**
	 * These two are normally taken from the $_GET and won't be set on fresh run of this widget
	 */
	protected $currentSortAttribute;
	protected $currentSortOrder;

	function sort()
	{
		if($this->sort){
			if(is_string($this->sort)){
				$this->currentSortAttribute = $this->sort;
				$this->currentSortOrder = 1;
			}elseif(is_array($this->sort)){
				list($field, $sort) = $this->sort;
				$this->currentSortAttribute = $field;
				$this->currentSortOrder = $sort;
			}
		}
		
		if($paramSort = glue::http()->param($this->sortParam))
			$this->currentSortAttribute = $paramSort;
		if($paramOrder = glue::http()->param($this->orderParam))
			$this->currentSortOrder = $paramOrder;	

		if($this->currentSortAttribute === null)
			return; // Do not process sort if none was provided		

		foreach($this->sortableAttributes as $k => $v){
			if(is_numeric($k) && $v == $this->currentSortAttribute){
				if($this->currentSortOrder == 1 || $this->currentSortOrder == -1)
					$this->cursor->sort(array($this->currentSortAttribute => (int)$this->currentSortOrder));
				else
					$this->cursor->sort(array($this->currentSortAttribute => 1)); // Default ASC
				break;
			}elseif($k === $this->currentSortAttribute){
				$sortableAttribute = $this->sortableAttributes[$k];
				if(is_array($sortableAttribute)){
					// Then check if this sort is allowed
					
					$sort = array_key_exists('sort', $sortableAttribute) ? $sortableAttribute['sort'] : $sortableAttribute;
					if(is_string($sort)){
						$order = $this->currentSortOrder == $sort ? $sort : 1;
					}elseif(in_array($this->currentSortOrder, $sort)){
						$order = $sort;	
					}else{
						$order = 1;
					}
				}else{
					$order = is_string($sortableAttribute) && $this->currentSortOrder == $sortableAttribute ? $sortableAttribute : 1; // By default make it ASC
				}
				
				$this->cursor->sort(array($this->currentSortAttribute => $order));		
				break;		
			}
		}
	}

	function render()
	{
		$this->itemCount = $this->cursor->count();

		if($this->enableSorting)
			$this->sort();

		// Get the current page
		if($this->enablePagination){
			$pager = Pagination::start(array_merge($this->pagination, array(
				'itemCount' => $this->itemCount,
				'params' => array_merge($this->data, array(
					$this->sortParam => $this->currentSortAttribute,
					$this->orderParam => $this->currentSortOrder
				))
			)));
			$this->cursor->skip($pager->getSkip())->limit($pager->getLimit());
			if($this->cursor instanceof \glue\db\Cursor){
				$this->cursor->all();
			}
			$html = preg_replace("/{pager}/", $pager->render(), $this->template);
		}

		ob_start();
		$i = 0;
		
		$data = $this->data;
		$data['i'] = $i;

		foreach($this->cursor as $_id => $item){
			$fn=$this->callback;
			$data['item'] = $item;
			
			if((is_string($fn) && function_exists($fn)) || (is_object($fn) && ($fn instanceof \Closure))){
				$fn($i,$item,$this->itemView);
			}else{
				if(is_string($this->itemView)){ // Is it a file location?
					echo glue::controller()->renderPartial($this->itemView, $data);
				}
			}
			$i++;
		}
		$items = ob_get_contents();
		ob_end_clean();

		$html = preg_replace("/{items}/", $items, $html);
		echo $html;
	}

	function createUrl($morph = array())
	{
		return glue::http()->url(array_merge($this->data, array(
			$this->sortParam => $this->currentSortAttribute,
			$this->orderParam => $this->currentSortOrder
		), $morph));
	}
}