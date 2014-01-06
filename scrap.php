<?php
public static function getModelFormVariableName($attribute, $model){
	if(($pos=strpos($attribute,'['))!==false)
	{
		if($pos!==0)  // e.g. name[a][b]
			return self::getModelShortName($model).'['.substr($attribute,0,$pos).']'.substr($attribute,$pos);
		if(($pos=strrpos($attribute,']'))!==false && $pos!==strlen($attribute)-1)  // e.g. [a][b]name
		{
			$sub=substr($attribute,0,$pos+1);
			$attribute=substr($attribute,$pos+1);
			return self::getModelShortName($model).$sub.'['.$attribute.']';
		}
		if(preg_match('/\](\w+\[.*)$/',$attribute,$matches))
		{
			$name=self::getModelShortName($model).'['.str_replace(']','][',trim(strtr($attribute,array(']['=>']','['=>']')),']')).']';
			$attribute=$matches[1];
			return $name;
		}
	}
	else
		return self::getModelShortName($model).'['.$attribute.']';
}

public static function getModelFormVariableValue($attribute, $model, $options = array()){
	if(isset($options['value'])){
		return $options['value'];
	}else{
		if(($pos=strpos($attribute,'['))!==false)
		{
			if($pos!==0){  // e.g. name[a][b]
				//var_dump($attribute);
				$exploded_path = explode('.', trim(strtr($attribute,array(']['=>'.','['=>'.')),']'));
				if(count($exploded_path) > 0){
					$previous = $model;
					foreach($exploded_path as $part){
						if(is_object($previous)){
							//var_dump($previous);
							if(!property_exists($previous, $part)) return null;
							$previous = $previous->$part;
						}else{
							if(!isset($previous[$part])) return null;
							$previous = $previous[$part];
						}
					}
					return $previous;
				}else{
					return $model->{substr($attribute,0,$pos)};
				}
			}
			if(($pos=strrpos($attribute,']'))!==false && $pos!==strlen($attribute)-1)  // e.g. [a][b]name
			{
				$sub=substr($attribute,0,$pos+1);
				$attribute=substr($attribute,$pos+1);
				return $model->$attribute;
			}
			if(preg_match('/\](\w+\[.*)$/',$attribute,$matches))
			{
				$name=self::getModelShortName($model).'['.str_replace(']','][',trim(strtr($attribute,array(']['=>']','['=>']')),']')).']';
				$attribute=$matches[1];
				return $model->$attribute;
			}
		}
		else
			return $model->$attribute;
	}
	return "";
}

static function getModelShortName($model){
	$d=explode('\\',get_class($model));
	return end($d);
}