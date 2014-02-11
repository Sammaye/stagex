<?php
<div class="advanced-search">
<div class="conditions">
				<?php ob_start(); ?>
				<div class="row condition-row">
				<div class="form-group col-md-2 col-sm-2 col-xs-6 condition_field"><?php echo $form->selectbox('field[]', app\models\Video::advancedSearchFields(), null, array('class' => 'form-control')) ?></div>
				<div class="form-group col-md-2 col-sm-2 col-xs-6 condition_type" style='width:140px;'>
				<?php echo $form->selectbox('search_op[]', array('=' => '=', '>' => '>', '>=' => '>=', '<' => '<', '<=' => '<=', '!=' => '!=', 
					'in' => 'IN', 'not_in' => 'NOT IN', 'like' => 'LIKE', 'not_like' => 'NOT LIKE', 'null' => 'Is Empty'), null, array('class' => 'form-control')) ?>
				</div>
				<div class="form-group col-md-5 col-sm-5 col-xs-7 condition_value">
				<div class="textvalue-value"><?php echo $form->textfield('value[]', null, array('placeholder' => 'Enter Search Query', 'class' => 'form-control')) ?></div>
				</div>
				<div class="form-group col-md-2 col-sm-2 col-xs-3 condition_operand" style='width:140px;'>
				<?php echo $form->selectbox('operand[]', array('and' => 'AND', 'or' => 'OR', 'and_or' => 'AND OR'), null, array('class' => 'form-control')) ?>
				</div>
				<div class="form-group col-md-1 col-sm-1 col-xs-2"><a class="btn-remove-condition" href="#">Remove</a></div>
				</div>
				<?php $html = ob_get_contents(); 
				ob_end_clean(); ?>
				
				<?php echo $html;
				$this->js('advanced_search', "
					$(document).on('click', '.advanced-search .btn-add-condition', function(e){
						e.preventDefault();
						$('.advanced-search .conditions').append(".js_encode($html).");
					});

					$(document).on('click', '.condition-row .btn-remove-condition', function(e){
						e.preventDefault();
						$(this).parents('.condition_row').remove();
					});
				");
				?>
				</div>
				<div><a href="#" class="btn btn-link btn-add-condition">Add condition</a></div>
			</div>
