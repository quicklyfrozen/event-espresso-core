<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
//
///**
// * Description of EE_Base_Class
// *
// * @author mnelson4
// */
//abstract class EE_Base_Class {	
//	/**
//	 * basic constructor for Event Espresso classes, performs any necessary initialization,
//	 * and verifies it's children play nice
//	 */
//	public function __construct($fieldValues=null){
//		$className=get_class($this);
//		do_action("action_hook_espresso__{$className}__construct",$this,$fieldValues);
//		$model=$this->_get_model();
//		if($fieldValues!=null){
//			foreach($fieldValues as  $fieldName=>$fieldValue){
//				//"<br>set $fieldName to $fieldValue";
//				$this->set($fieldName,$fieldValue,true);
//			}
//		}
//		//verify we have all the attributes required in teh model
//		foreach($model->field_settings() as $fieldName=>$fieldSettings){
//			if(!property_exists($this,$this->_get_private_attribute_name($fieldName))){
//				throw new EE_Error(sprintf(__('You have added an attribute titled \'%s\' to your model %s, but have not set a corresponding
//					attribute on %s. Please add $%s to %s','event_espresso'),
//						$fieldName,get_class($model),get_class($this),$this->_get_private_attribute_name($fieldName),get_class($this)));
//			}
//		}
//		//verify we have all the model relations
//		foreach($model->relation_settings() as $relationName=>$relationSettings){
//			if(!property_exists($this,$this->_get_private_attribute_name($relationName))){
//				throw new EE_Error(sprintf(__('You have added a relation titled \'%s\' to your model %s, but have not set a corresponding
//					attribute on %s. Please add protected $%s to %s','event_espresso'),
//						$relationName,get_class($model),get_class($this),$this->_get_private_attribute_name($relationName),get_class($this)));
//			}
//		}
//	}
//	
//	/**
//	 * Gets the EEM_*_Model for this class
//	 * @access public now, as this is more convenient 
//	 * @return EEM_Base
//	 */
//	public function  _get_model(){
//		//find model for this class
//		$modelName=$this->_get_model_name();
//		require_once($modelName.".model.php");
//		//$modelObject=new $modelName;
//		$model=call_user_func($modelName."::instance");
//		return $model;
//	}
//	
//	/**
//	 * Gets the model's name for this class. Eg, if this class' name is 
//	 * EE_Answer, it will return EEM_Answer.
//	 * @return string
//	 */
//	private function _get_model_name(){
//		$className=get_class($this);
//		$modelName=str_replace("EE_","EEM_",$className);
//		return $modelName;
//	}
//	
//	
//	/**
//	 * converts a field name to the private attribute's name on teh class.
//	 * Eg, converts "ANS_ID" to "_ANS_ID", which can be used like so $attr="_ANS_ID"; $this->$attr;
//	 * @param string $fieldName
//	 * @return string
//	 */
//	private function _get_private_attribute_name($fieldName){
//		return "_".$fieldName;
//	}
//	
//	/**
//	 * gets the field (class attribute) specified by teh given name
//	 * @param string $fieldName if the field you want is named $_ATT_ID, use 'ATT_ID' (omit preceding underscore)
//	 * @return mixed
//	 */
//	public function get($fieldName){
//		$privateFieldName=$this->_get_private_attribute_name($fieldName);
//		$fieldSettings=$this->get_field_settings();
//		if(array_key_exists($fieldName,$fieldSettings)){
//			$value=$this->$privateFieldName;
//			$thisFieldSettings=$fieldSettings[$fieldName];
//			if( $thisFieldSettings->nullable() && $value == null){
//				return null;
//			}elseif(!$thisFieldSettings->nullable() && $value == null){
//				EE_Error::add_error( sprintf( __("Some data is missing||The field named %s on %s is null, but it shouldnt be. The complete object is:%s",'event_espresso'),$fieldName,get_class($this),  print_r($this, true)), __FILE__, __FUNCTION__, __LINE__ );
//				return null;
//			}
//			switch($thisFieldSettings->type()){
//				case 'primary_key':
//				case 'foreign_key':
//				case 'int':
//					return intval($value);
//				case 'bool':
//				case 'deleted_flag':
//					//$value=intval($value);
//					return $value==true;
//					break;
//				case 'primary_text_key':
//				case 'foreign_text_key':
//				case 'plaintext':
//				case 'simplehtml':
//				case 'fullhtml':
//				case 'email':
//				case 'all_caps_key':
//					return $value;
//				case 'float':
//					return floatval($value);
//				case 'date':
//					return intval($value);
//				case 'enum':
//					return $value;
//					break;
//				case 'serialized_text':
//					//a serialized_text field SHOULD be an array right now,
//					//but in case it isn't unserialize it
//					return maybe_unserialize($value);
//			}
//		}else{
//			EE_Error::add_error(sprintf(__("You have requested a field named %s on model %s",'event_espresso'),$fieldName,get_class($this)), __FILE__, __FUNCTION__, __LINE__);
//			RETURN FALSE;
//		}
//	}
//	
//	
//	/**
//	 * Sets the class attribute by the specified name to the value.
//	 * Uses the _fieldSettings attribute to 
//	 * @param string $attributeName, as it appears on teh DB column (no _ prefix)
//	 * @param mixed $value
//	 * @param boolean $useDefault if $value is null and $useDefault is true, retrieve a default value from the EEM_Base's EE_Model_Field.
//	 * @return null
//	 */
//	public function set($fieldName,$value,$useDefault=false){
//		$fields=$this->get_field_settings();
//		if(!array_key_exists($fieldName, $fields)){
//			throw new EE_Error(sprintf(__("An internal Event Espresso error has occured. Please contact Event Espresso.||The field %s doesnt exist on Event Espresso class %s",'event_espresso'),$fieldName,get_class($this)));
//		}
//		$fieldSettings=$fields[$fieldName];
//		//if this field doesn't allow nulls, check it isn't null
//		if($value===null && $useDefault){
//			$privateAttributeName=$this->_get_private_attribute_name($fieldName);
//			$modelFields=$this->_get_model()->field_settings();
//			$defaultValue=$modelFields[$fieldName]->default_value();
//			$this->$privateAttributeName=$defaultValue;
//			return true;
//		}elseif($value===null & !$useDefault){
//			if(!$fieldSettings->nullable()){
//				$msg = sprintf( __( 'Event Espresso error setting value on field %s.||Field %s on class %s cannot be null, but you are trying to set it to null!', 'event_espresso' ), $fieldName,$fieldName,get_class($this));
//				EE_Error::add_error( $msg, __FILE__, __FUNCTION__, __LINE__ );
//				return false;
//			}else{
//				$privateAttributeName=$this->_get_private_attribute_name($fieldName);
//				$this->$privateAttributeName=$value;
//				return true;
//			}
//		}else{
//			//verify its of the right type
//			if($this->_verify_field_type($value,$fieldSettings)){
//				$internalFieldName=$this->_get_private_attribute_name($fieldName);
//				$this->$internalFieldName=$this->_sanitize_field_input($value, $fieldSettings);
//				return true;
//			}else{
//				$msg = sprintf( __( 'Event Espresso error setting value on field %s.||In trying to set field %s of class %s to value %s, it was found to not be of type %s', 'event_espresso' ), $fieldName,$fieldName,get_class($this),print_r($value,true),$fieldSettings->type());
//				EE_Error::add_error( $msg, __FILE__, __FUNCTION__, __LINE__ );
//				return false;
//			}
//		}
//	}
//	
//	/**
//	 * Sanitizes (or cleans) the value. Ie, 
//	 * @param mixed $value
//	 * @param EE_Model_Field $fieldSettings
//	 * @return boolean of success
//	 * @throws EE_Error
//	 */
//	protected function _sanitize_field_input($value,$fieldSettings){
//		$return=null;
//		switch($fieldSettings->type()){
//			case 'primary_key':
//				$return=absint($value);
//				break;
//			case 'foreign_key':
//				$return=absint($value);
//				break;
//			case 'int':
//				$return=intval($value);
//				break;
//			case 'bool':
//			case 'deleted_flag':
//				if($value){
//					$return=true;
//				}else{
//					$return=false;
//				}
//				break;
//			case 'plaintext':
//			case 'primary_text_key':
//			case 'foreign_text_key':
//				$return=htmlentities(wp_strip_all_tags("$value"), ENT_QUOTES, 'UTF-8' );
//				break;
//			case 'simplehtml':
//				global $allowedtags;
//				$allowedtags['ol']=array();
//				$allowedtags['ul']=array();
//				$allowedtags['li']=array();
//				$return=  htmlentities(wp_kses("$value",$allowedtags),ENT_QUOTES,'UTF-8');
//				break;
//			case 'fullhtml':
//				$return= htmlentities("$value",ENT_QUOTES,'UTF-8');
//				break;
//			case 'email':
//				$return = sanitize_email($value);
//				break;
//			case 'all_caps_key':
//				$return = strtoupper( sanitize_key($value));
//				break;
//			case 'float':
//				$return=floatval( preg_replace( "/^[^0-9\.]-/", "", preg_replace( "/,/", ".", $value ) ));
//				break;
//			case 'date':
//				//check if we've been given a string representing a time.
//				if(!is_numeric($value)){
//					//if so, try to convert it to unix timestamp
//					$value=strtotime($value);
//				}
//				$return = intval($value);
//				break;
//			case 'enum':
//				if($value!==null && !in_array($value,$fieldSettings->allowed_enum_values())){
//					$msg = sprintf(__("System is assigning imcompatible value '%s' to field '%s'",'event_espresso'),$value,$fieldSettings->nicename());
//					$msg2 = sprintf(__("Allowed values for '%s' are %s",'event_espresso'),$fieldSettings->nicename(),$fieldSettings->allowed_enum_values());
//					throw new EE_Error("$msg||$msg2");
//				}
//				$return=$value;
//				break;
//			case 'serialized_text':
//				//serialized_text should be an array at this point. Right before saving, we'll serialize it
//				//so in case someone's already serialized it, unserialize it.
//				//also, if we've just fetched this from the DB, it's serialized. Unserialized it.
//				$value = maybe_unserialize($value);
//				
//				$return=$value;
//				break;
//		}
//		$return=apply_filters('filter_hook_espresso_sanitizeFieldInput',$return,$value,$fieldSettings);//allow to be overridden
//		if(is_null($return)){
//			throw new EE_Error(sprintf(__("Internal Event Espresso error. Field %s on class %s is of type %s","event_espresso"),$fieldSettings->nicename(),get_class($this),$fieldSettings->type()));
//		}
//		return $return;
//	}
//	
//	/**
//	 * verifies that the specified field is of the correct type
//	 * @param mixed $value the value to check if it's of the correct type
//	 * @param EE_Model_Field $fieldSettings settings for a specific field. 
//	 * @return boolean
//	 * @throws EE_Error if fieldSettings is misconfigured
//	 */
//	protected function _verify_field_type($value,  EE_Model_Field $fieldSettings){
//		$return=false;
//		switch($fieldSettings->type()){
//			case 'primary_key':
//			case 'foreign_key':
//			case 'int':
//				if(ctype_digit($value) || is_numeric($value)){
//					$return= true;
//				}
//				break;
//			case 'bool':
//			case 'deleted_flag':
//				//$value=intval($value);
//				if(is_bool($value) || is_int($value) || ctype_digit($value)){
//					$return=true;
//				}
//				break;
//			case 'primary_text_key':
//			case 'foreign_text_key':
//			case 'plaintext':
//			case 'simplehtml':
//			case 'fullhtml':
//			case 'email':
//			case 'all_caps_key':
//				if(is_string($value)){
//					$return= true;
//				}
//				break;
//			case 'float':
//				if(is_numeric($value)){
//					$return= true;
//				}
//				break;
//			case 'date':
//				//@todo could verify date format here maybe.
//				//if we were to do that, the EE_Model_Field should take an input
//				//specifying teh date's format
//				if(is_int($value) || is_string($value)){
//					$return = true;
//				}
//				break;				
//			case 'enum':
//				$allowedValues=$fieldSettings->allowed_enum_values();
//				if(in_array($value,$allowedValues) || in_array(intval($value),$allowedValues)){
//					$return=true;
//				}
//				break;
//			case 'serialized_text'://accept anything. even if it's not an array, or if it's not yet serialized. we'll deal with it.
//				$return=true;
//		}
//		$return= apply_filters('filter_hook_espresso_verifyFieldIsOfCorrectType',$return,$value,$fieldSettings);//allow to be overridden
//		if(is_null($return)){
//			throw new EE_Error(sprintf(__("Internal Event Espresso error. Field %s on class %s is of type %s","event_espresso"),$fieldSettings->nicename,get_class($this),$fieldSettings->type()));
//		}
//		return $return;
//	}
//	
//	
//	
//	/**
//	 * To be used in template to immediately echo out the value, and format it for output.
//	 * Eg, shoudl call stripslashes and whatnought before echoing
//	 * @param string $fieldName the name of the field as it appears in teh DB
//	 * @return void
//	 */
//	public function e($fieldName){
//		$privateFieldName=$this->_get_private_attribute_name($fieldName);
//		$fieldSettings=$this->get_field_settings();
//		if(array_key_exists($fieldName,$fieldSettings)){
//			$value=$this->$privateFieldName;
//			$thisFieldSettings=$fieldSettings[$fieldName];
//			switch($thisFieldSettings->type()){
//				case 'primary_key':
//				case 'foreign_key':
//				case 'int':
//					echo intval($value);
//					break;
//				case 'bool':
//				case 'deleted_flag':
//					if($value){
//						_e("Yes",'event_espresso');
//					}else{
//						_e("No",'event_espresso');
//					}
//					break;
//				case 'primary_text_key':
//				case 'foreign_text_key':
//				case 'plaintext':
//				case 'simplehtml':
//				case 'fullhtml':
//				case 'email':
//				case 'all_caps_key':
//					echo stripslashes($value);
//					break;
//				case 'float':
//					echo floatval($value);
//					break;
//				case 'date':
//					
//					$date_format = get_option('date_format');
//					$time_format = get_option('time_format');//time's good, but 
//					//it's in the server's local time which might confuse peopel
//					if ( empty( $value )) {
//						_e("Unknown",'event_espresso');
//					} else {
//						echo date_i18n( $date_format, strtotime( $value )); 
//					}
//					break;
//				case 'enum':
//					echo stripslashes($value);
//					break;
//				case 'serialized_text'://accept anything. even if it's not an array, or if it's not yet serialized. we'll deal with it.
//					if(is_array($value)){
//						echo stripslashes($value);
//					}else{
//						echo stripslashes(unserialize($value));
//					}
//			}
//		}else{
//			EE_Error::add_error(sprintf(__("You have requested a field named %s on model %s",'event_espresso'),$fieldName,get_class($this)), __FILE__, __FUNCTION__, __LINE__);
//			return;
//		}
//	}
//	
//	/**
//	 * retrieves all the fieldSettings on this class
//	 * @return EE_Model_Field[]
//	 * @throws EE_Error
//	 */
//	public function get_field_settings(){
//		if($this->_get_model()->field_settings()==null){
//			throw new EE_Error(sprintf("An unexpected error has occured with Event Espresso.||An Event Espresso class has not been fully implemented. %s does not override the \$_fieldSettings attribute.",get_class($this)),"event_espresso");
//		}
//		return $this->_get_model()->field_settings();
//	}
//	
//	/**
//	*		Saves this object to teh database. An array may be supplied to set some values on this
//	 * object just before saving.
//	* 
//	* 		@access		public
//	* 		@param		array		$set_cols_n_values		
//	*		@return int, 1 on a successful update, the ID of
//	*					the new entry on insert; 0 on failure		
//	
//	*/	
//	public function save($set_cols_n_values=array()) {
//		//set attributes as provided in $set_cols_n_values
//		foreach($set_cols_n_values as $column=>$value){
//			$this->set($column,$value);
//		}
//		//now get current attribute values
//		$save_cols_n_values = array();
//		foreach($this->get_field_settings() as $fieldName=>$fieldSettings){
//			$attributeName=$this->_get_private_attribute_name($fieldName);
//			if($fieldSettings->type() == 'serialized_text'){
//				//serialized_text fields should be arrays/objects 
//				$save_cols_n_values[$fieldName] = maybe_serialize($this->$attributeName);
//			}else{
//				$save_cols_n_values[$fieldName] = $this->$attributeName;
//			}	
//		}
//		if ( $save_cols_n_values[$this->_get_primary_key_name()]!=null ){
//			$results = $this->_get_model()->update ( $save_cols_n_values, array($this->_get_primary_key_name()=>$this->get_primary_key()) );
//		} else {
//			unset($save_cols_n_values[$this->_get_primary_key_name()]);
//			
//			$results = $this->_get_model()->insert ( $save_cols_n_values );
//			if($results){//if successful, set the primary key
//				$results=$results['new-ID'];
//				$this->set($this->_get_primary_key_name(),$results);//for some reason the new ID is returned as part of an array,
//				//where teh only key is 'new-ID', and it's value is the new ID.
//			}
//		}
//		
//		return $results;
//	}
//	
//	/**
//	 * returns the name of the primary key attribute
//	 * @return string
//	 */
//	private function _get_primary_key_name(){
//		return $this->_get_model()->primary_key_name();
//	}
//	
//	/**
//	 * Returns teh value of the primary key for this class. false if there is none
//	 * @return int
//	 */
//	public function get_primary_key(){
//		$pk=$this->_get_private_attribute_name($this->_get_primary_key_name());
//		return $this->$pk;//$pk is the primary key's NAME, so get the attribute with that name and return it
//	}
//	
//	/**
//	 * Functions through which all other calls to get a single related model object is passed.
//	 * Handy for common logic between them, eg: caching.
//	 * @param string $relationName
//	 * @return EE_Base_Class
//	 */
//	public function get_first_related( $relationName, $where_col_n_values = null, $orderby = null, $order = null, $operators = '=', $output = 'OBJECT_K'){
//		$internalName=$this->_get_private_attribute_name($relationName);
//		//cache the related object
//		//if($this->$internalName==null){
//			$model=$this->_get_model();
//			$relationRequested=$model->get_first_related($this, $relationName,$where_col_n_values,$orderby,$order,$operators,$output);
//			$this->$internalName=$relationRequested;
//		//}
//		//return teh now-cahced related object
//		return $this->$internalName;
//	}
//	
//	/**
//	 * Removes all caches on relations. E.g., on EE_Question, if you've previously asked for
//	 * all teh related EE_Answers -and had that list automatically cahced- and remove one of those EE_Answers, this function will clear that cache.
//	 * @param string $specificRelationName if you know exactly which relation cache needs to be cleared. If not set, all of them will be cleared.
//	 */
//	public function clear_relation_cache( $specificRelationName = null ){
//		if(!$specificRelationName){
//			$model=$this->_get_model();
//			$relations=array_keys($model->relation_settings());
//			foreach($relations as $relationName){
//				$privateAttributeName=$this->_get_private_attribute_name($relationName);
//				$this->$privateAttributeName=null;
//			}
//		}else{
//			$privateAttributeName=$this->_get_private_attribute_name($specificRelationName);
//			$this->$privateAttributeName=null;
//		}
//	}
//	
//	/**
//	 * Function through which all other calls to get many related model objects is passed.
//	 * Handy for common lgoci between them, eg: caching.
//	 * @param string $relationName
//	 * @param array $where_col_n_vals keys are field/column names, values are their values
//	 * @return EE_Base_Class[]
//	 */
//	public function get_many_related($relationName,$where_col_n_values=null,$orderby=null,$order='ASC',$operators='=',$limit=null,$output='OBJECT_K'){
//		$privateRelationName=$this->_get_private_attribute_name($relationName);
//		//if($this->$privateRelationName==null){
//			$model=$this->_get_model();
//			$relationRequested=$model->get_many_related($this, $relationName,$where_col_n_values,$orderby,$order,$operators,$limit,$output);
//			$this->$privateRelationName=$relationRequested;
//		//}
//		return $this->$privateRelationName;
//	}
//	
//	/**
//	 * Adds a relationship to the specified EE_Base_Class object, given the relationship's name. Eg, if the curren tmodel is related
//	 * to a group of events, the $relationName should be 'Events', and should be a key in the EE Model's $_model_relations array
//	 * @param mixed $otherObjectModelObjectOrID EE_Base_Class or the ID of the other object
//	 * @param string $relationName eg 'Events','Question',etc.
//	 * @param array $extraColumnsForHABTM mapping from column/attribute names to values for JOIN tables with extra columns. Eg, when adding 
//	 * an attendee to a group, you also want to specify which role they will have in that group. So you would use this parameter to specificy array('role-column-name'=>'role-id')
//	 
//	 * @return boolean success
//	 */
//	public function _add_relation_to($otherObjectModelObjectOrID,$relationName,$extraColumnsForHABTM=null){
//		$model=$this->_get_model();
//		$success= $model->_add_relation_to($this, $otherObjectModelObjectOrID, $relationName,$extraColumnsForHABTM);
//		if($success){
//			//invalidate cached relations
//			//@todo: this could be optimized. Instead, we could just add $otherObjectModel toteh array if it's an array, or set it if it isn't an array
//			$this->clear_relation_cache($relationName);
//			if($otherObjectModelObjectOrID instanceof EE_Base_Class){
//				$otherObjectModelObjectOrID->clear_relation_cache();
//			}
//			return $success;
//		}else{
//			return $success;
//		}
//	}
//	
//	/**
//	 * Removes a relationship to the psecified EE_Base_Class object, given the relationships' name. Eg, if the curren tmodel is related
//	 * to a group of events, the $relationName should be 'Events', and should be a key in the EE Model's $_model_relations array
//	 * @param mixed $otherObjectModelObjectOrID EE_Base_Class or the ID of the other object
//	 * @param string $relationName
//	 * @return boolean success
//	 */
//	public function _remove_relation_to($otherObjectModelObjectOrID,$relationName){
//		$model=$this->_get_model();
//		$success= $model->remove_relationship_to($this, $otherObjectModelObjectOrID, $relationName);
//		if($success){
//			//invalidate cached relations
//			//@todo: this could be optimized. Instead, we could just remove $otherObjectModel toteh array if it's an array, or unset it if it isn't an array
//			$this->clear_relation_cache($relationName);
//			if($otherObjectModelObjectOrID instanceof EE_Base_Class){
//				$otherObjectModelObjectOrID->clear_relation_cache();
//			}
//			return $success;
//		}else{
//			return $success;
//		}
//	}
//	/**
//	 * Wrapper for get_primary_key(). Gets the value of the primary key.
//	 * @return mixed, if the primary key is of type INT it'll be an int. Otherwise it could be a string
//	 */
//	public function ID(){
//		$r=$this->get_primary_key();
//		return $r;
//	}
//	
//	/**
//	 * Deletes this model object. That may mean just 'soft deleting' it though.
//	 * @return boolean success
//	 */
//	public function delete(){
//		$model=$this->_get_model();
//		$result=$model->delete_by_ID($this->ID());
//		if($result){
//			return true;
//		}else{
//			return false;
//		}
//	}
//	
//	/**
//	 * Very handy general function to allow for plugins to extend any child of EE_Base_Class.
//	 * If a method is called on a child of EE_Base_Class that doesn't exist, this function is called (http://www.garfieldtech.com/blog/php-magic-call)
//	 * and passed the method's name and arguments.
//	 * Instead of requiring a plugin to extend the EE_Base_Class (which works fine is there's only 1 plugin, but when will that happen?)
//	 * they can add a hook onto 'filters_hook_espresso__{className}__{methodName}' (eg, filters_hook_espresso__EE_Answer__my_great_function)
//	 * and accepts 2 arguments: the object on which teh function was called, and an array of the original arguments passed to the function. Whatever their callbackfunction returns will be returned by this function.
//	 * Example: in functions.php (or in a plugin):
//	 * add_filter('filter_hook_espresso__EE_Answer__my_callback','my_callback',10,3);
//	 * function my_callback($previousReturnValue,EE_Base_Class $object,$argsArray){
//			$returnString= "you called my_callback! and passed args:".implode(",",$argsArray);
//	 *		return $previousReturnValue.$returnString;
//	 * }
//	 * require('EE_Answer.class.php');
//	 * $answer=new EE_Answer(2,3,'The answer is 42');
//	 * echo $answer->my_callback('monkeys',100);
//	 * //will output "you called my_callback! and passed args:monkeys,100"
//	 * @param string $methodName name of method which was called on a child of EE_Base_Class, but which 
//	 * @param array $args array of original arguments passed to the function
//	 * @return mixed whatever the plugin which calls add_filter decides
//	 */
//	public function __call($methodName,$args){
//		$className=get_class($this);
//		$tagName="filter_hook_espresso__{$className}__{$methodName}";
//		if(!has_filter($tagName)){
//			throw new EE_Error(sprintf(__("Method %s on class %s does not exist! You can create one with the following code in functions.php or in a plugin: add_filter('%s','my_callback',10,3);function my_callback(\$previousReturnValue,EE_Base_Class \$object, \$argsArray){/*function body*/return \$whatever;}","event_espresso"),
//										$methodName,$className,$tagName));
//		}
//		return apply_filters($tagName,null,$this,$args);
//	}
//	
//}
//
//

class EE_Base_Class{


	/**
	 * Timezone
	 * This gets set by the "set_timezone()" method so that we know what timezone incoming strings|timestamps are in.  This can also be used before a get to set what timezone you want strings coming out of the object to be in.  NOT all EE_Base_Class child classes use this property but any that use a EE_Datetime_Field data type will have access to it.
	 * @var string
	 */
	protected $_timezone = NULL;




	/**
	 * This property is for holding a cached array of object properties indexed by property name as the key.
	 * The purpose of this is for setting a cache on properties that may have calculated values after a prepare_for_get.  That way the cache can be checked first and the calculated property returned instead of having to recalculate.
	 *
	 * Used by _set_cached_property() and _get_cached_property() methods.
	 * @access protected
	 * @var array
	 */
	protected $_cached_properties = array();


	/**
	 * basic constructor for Event Espresso classes, performs any necessary initialization,
	 * and verifies it's children play nice
	 * @param array $fieldValues where each key is a field (ie, array key in the 2nd layer of the model's _fields array, (eg, EVT_ID, TXN_amount, QST_name, etc) and valuse are their values
	 * @param boolean $bydb a flag for setting if the class is instantiated by the corresponding db model or not.
	 * 
	 */
	public function __construct($fieldValues=null, $bydb = FALSE){
		$className=get_class($this);
		do_action("action_hook_espresso__{$className}__construct",$this,$fieldValues);
		$model=$this->_get_model();
		
		//if db model is instantiatiating
		if( $bydb ){
			//the primary key is in the constructor's first arg's array, so assume we're constructing from teh DB
			//(otherwise: why would we already know the primary key's value, unless we fetched it from the DB?)
			foreach($fieldValues as $field_name => $field_value_from_db){
				$this->set_from_db($field_name,$field_value_from_db);
			}
		}else{
			//the primary key  isn't in the constructor's first arg's array, so assume we're constructing a brand
			//new instance of the model object. Generally, this means we'll need to do more field validation
			foreach($fieldValues as $fieldName => $fieldValue){
				$this->set($fieldName,$fieldValue,true);
			}
		}
		//verify we have all the attributes required in teh model
		foreach($model->field_settings() as $fieldName=>$field_obj){
			if( ! $field_obj->is_db_only_field() && ! property_exists($this,$this->_get_private_attribute_name($fieldName))){
				throw new EE_Error(sprintf(__('You have added an attribute titled \'%s\' to your model %s, but have not set a corresponding
					attribute on %s. Please add $%s to %s','event_espresso'),
						$fieldName,get_class($model),get_class($this),$this->_get_private_attribute_name($fieldName),get_class($this)));
			}
		}
//		//verify we have all the model relations
		foreach($model->relation_settings() as $relationName=>$relationSettings){
			if(!property_exists($this,$this->_get_private_attribute_name($relationName))){
				throw new EE_Error(sprintf(__('You have added a relation titled \'%s\' to your model %s, but have not set a corresponding
					attribute on %s. Please add protected $%s to %s','event_espresso'),
						$relationName,get_class($model),get_class($this),$this->_get_private_attribute_name($relationName),get_class($this)));
			}
		}
	}
	/**
	 * Overrides parent because parent expects old models.
	 * This also doesn't do any validation, and won't work for serialized arrays
	 * @param type $field_name
	 * @param type $field_value
	 * @param type $use_default
	 */
	public function set($field_name,$field_value,$use_default= false){
		$privateAttributeName=$this->_get_private_attribute_name($field_name);
		$field_obj = $this->_get_model()->field_settings_for($field_name);
		 $holder_of_value = $field_obj->prepare_for_set($field_value);
		 if( ($holder_of_value === NULL || $holder_of_value ==='') && $use_default){
			 $this->$privateAttributeName = $field_obj->get_default_value();
		 }else{
			$this->$privateAttributeName = $holder_of_value; 
		 }

		 //let's unset any cache for this field_name from the $_cached_properties property.
		 if ( isset( $this->_cached_properties[$privateAttributeName] ) )
		 	unset( $this->_cached_properties[$private_AttributeName] );
		 
	}




	/**
	 * See $_timezone property for description of what the timezone property is for.  This SETS the timezone internally for being able to refernece what timezone we are running conversions on when converting TO the internal timezone (UTC Unix Timestamp) for the object OR when converting FROM the internal timezone (UTC Unix Timestamp).
	 *  This is available to all child classes that may be using the EE_Datetime_Field for a field data type.
	 *
	 * @access public
	 * @param string $timezone A valid timezone string as described by @link http://www.php.net/manual/en/timezones.php
	 * @return void
	 */
	public function set_timezone( $timezone ) {
		$timezone = empty( $timezone ) ? get_option( 'timezone_string' ) : $timezone;
		EE_Datetime_Field::validate_timezone( $timezone ); //just running validation on the timezone.
		$this->_timezone = $timezone;
		//make sure we clear all cached properties because they won't be relevant now
		$this->_clear_cached_properties();
	}




	/**
	 * This just returns whatever is set for the current timezone.
	 *
	 * @access public
	 * @return string timezone string
	 */
	public function get_timezone() {
		return $this->_timezone;
	}




	
	/**
	 * Remembers the model object on the current model object. In certain circumstances,
	 * we can use this cached model object instead of querying for another one entirely.
	 * @param EE_Base_Class $object_to_cache that has a relation to this model object. (Eg, 
	 * if this is a Transaction, that could be a payment or a registration)
	 * @param string $relationName one of the keys in the _model_relations array on the model. Eg 'Registration'
	 * assocaited with this model object
	 * @return boolean success
	 */
	public function cache($relationName,$object_to_cache){
		$relationNameClassAttribute = $this->_get_private_attribute_name($relationName);
		$relationship_to_model = $this->_get_model()->related_settings_for($relationName);
		if( ! $relationship_to_model){
			throw new EE_Error(sprintf(__("There is no relationship to %s on a %s. Cannot cache it",'event_espresso'),$relationName,get_class($this)));
		}
		if($relationship_to_model instanceof EE_Belongs_To_Relation){
			//if it's a belongs to relationship, there's only one of those model objects
			//for each of these model objects (eg, if this is a registration, there's only 1 attendee for it)
			//so, just set it to be cached
			$this->$relationNameClassAttribute = $object_to_cache;
		}else{
			//there can be many of those other objects for this one.
			//just add it to the array of related objects of that type.
			//eg: if this is an event, there are many registrations to that event
			if( ! is_array($this->$relationNameClassAttribute)){
				$this->$relationNameClassAttribute = array();
			}
			$this->{$relationNameClassAttribute}[$object_to_cache->ID()]=$object_to_cache;
		}
		return true;
	}




	/**
	 * For adding an item to the cached_properties property.
	 *
	 * @access protected
	 * @param string $propertyname the property item the corresponding value is for.
	 * @param mixed  $value        The value we are caching.
	 * @return void
	 */
	protected function _set_cached_property( $propertyname, $value ) {
		//first make sure this property exists
		if ( !property_exists( $this, $propertyname ) )
			throw new EE_Error( sprintf( __('Trying to cache a non-existent property (%s).  Doublecheck the spelling please', 'event_espresso'), $propertyname ) );
		$this->_cached_properties[$propertyname] = $value;
	}





	/**
	 * This returns the value cached property if it exists OR the actual property value if the cache doesn't exist.
	 * This also SETS the cache if we return the actual property!
	 * @param  string $propertyname the name of the property we're trying to retrieve
	 * @return mixed                whatever the value for the property is we're retrieving
	 */
	protected function _get_cached_property( $propertyname ) {
		//first make sure this property exists
		if ( !property_exists( $this, $propertyname ) )
			throw new EE_Error( sprintf( __('Trying to retrieve a non-existent property (%s).  Doublecheck the spelling please', 'event_espresso'), $propertyname ) );

		if ( isset( $this->_cached_properties[$property_name] ) ) {
			return $this->_cached_properties[$property_name];
		}

		//otherwise let's return the property
		$field_name = ltrim( $propertyname, '_' );
		$field_obj = $this->_get_model()->field_settings_for($field_name);
		$value = $field_obj->prepare_for_get($this->$privateAttributeName );
		$this->_set_cached_property( $property_name, $value );
		return $value;
	}




	/**
	 * This just takes care of clearing out the cached_properties 
	 * @return void
	 */
	protected function _clear_cached_properties() {
		$this->_cached_properties = array();
	}


	
	/**
	 * Ensures that this related thing is a model object.
	 * @param mixed $object_or_id EE_base_Class/int/string either a rellate dmodel object, or its ID
	 * @param string $model_name name of the related thing, eg 'Attendee',
	 * @return EE_Base_Class
	 */
	protected function ensure_related_thing_is_model_obj($object_or_id,$model_name){
		$other_model_instance = $this->_get_model_instance_with_name($this->_get_model_classname($model_name));
		$model_obj = $other_model_instance->ensure_is_obj($object_or_id);
		return $model_obj;
	}
	
	/**
	 * Forgets the cached model of the given relation Name. So the next time we request it, 
	 * we will fetch it again from teh database. (Handy if you know it's changed somehow).
	 * If a specific object is supplied, and the relationship to it is either a HasMany or HABTM,
	 * then only remove that one object from our cached array. Otherwise, clear the entire list.
	 * @param string $relationName one of the keys in the _model_relations array on the model. Eg 'Registration'
	 * @param EE_Base_Class $object_to_remove_from_cache
	 * @return boolean success
	 */
	public function clear_cache($relationName, $object_to_remove_from_cache = null){
		$object_to_remove_from_cache = $this->ensure_related_thing_is_model_obj($object_to_remove_from_cache, $relationName);
		$relationship_to_model = $this->_get_model()->related_settings_for($relationName);
		if( ! $relationship_to_model){
			throw new EE_Error(sprintf(__("There is no relationship to %s on a %s. Cannot clear that cache",'event_espresso'),$relationName,get_class($this)));
		}
		if($object_to_remove_from_cache !== null && ! ($object_to_remove_from_cache instanceof EE_Base_Class)){
			throw new EE_Error(sprintf(__("You have requested to remove the cached relationship to %s, but have not provided a model object. Instead you provided a %s",'event_espresso'),$relationName,get_class($object_to_remove_from_cache)));
		}
		$relationNameClassAttribute = $this->_get_private_attribute_name($relationName);
		if($relationship_to_model instanceof EE_Belongs_To_Relation){
			$this->$relationNameClassAttribute  = null;
		}else{
			if($object_to_remove_from_cache){
				//throw new EE_Error("removing an individual item from teh cahce not fully working yet");
				unset($this->{$relationNameClassAttribute}[$object_to_remove_from_cache->ID()]);
			}else{
				$this->$relationNameClassAttribute = null;
			}
		}
		return true;
	}
	
	/**
	 * Fetches a single EE_Base_Class on that relation. (If the relation is of type
	 * BelongsTo, it will only ever have 1 object. However, other relations could have an array of objects)
	 * 
	 * @param string $relationName
	 * @return EE_Base_Class
	 */
	public function get_one_from_cache($relationName){
		$relationNameClassAttribute = $this->_get_private_attribute_name($relationName);
		$cached_array_or_object =  $this->$relationNameClassAttribute;
		if(is_array($cached_array_or_object)){
			return array_shift($cached_array_or_object);
		}else{
			return $cached_array_or_object;
		}
	}
	
	
	
	/**
	 * Fetches a single EE_Base_Class on that relation. (If the relation is of type
	 * BelongsTo, it will only ever have 1 object. However, other relations could have an array of objects)
	 * @param string $relationName
	 * @return EE_Base_Class[]
	 */
	public function get_all_from_cache($relationName){
		$relationNameClassAttribute = $this->_get_private_attribute_name($relationName);
		$cached_array_or_object =  $this->$relationNameClassAttribute;
		if(is_array($cached_array_or_object)){
			return $cached_array_or_object;
		}elseif($cached_array_or_object){//if the result isnt an array, but exists, make it an array
			return array($cached_array_or_object);
		}else{//if nothing was found, return an empty array
			return array();
		}
	}
	
	
	/**
	 * Overrides parent because parent expects old models.
	 * This also doesn't do any validation, and won't work for serialized arrays
	 * @param type $field_name
	 * @param type $field_value_from_db
	 * @param type $use_default
	 */
	public function set_from_db($field_name,$field_value_from_db){
		$privateAttributeName=$this->_get_private_attribute_name($field_name);
		$field_obj = $this->_get_model()->field_settings_for($field_name);
		$this->$privateAttributeName = $field_obj->prepare_for_set_from_db($field_value_from_db);
	}
	
	
	/**
	 * gets the field (class attribute) specified by teh given name
	 * @param string $field_name if the field you want is named $_ATT_ID, use 'ATT_ID' (omit preceding underscore)
	 * @return mixed
	 */
	public function get($field_name){
		$privateAttributeName=$this->_get_private_attribute_name($field_name);
		return $this->_get_cached_property( $privateAttributeName );
	}
	
	/**
	 * To be used in template to immediately echo out the value, and format it for output.
	 * Eg, shoudl call stripslashes and whatnought before echoing
	 * @param string $field_name the name of the field as it appears in teh DB
	 * @return void
	 */
	public function e($field_name){
		echo $this->get_pretty($field_name);
	}
	
	/**
	 * 
	 * @param string $field_name
	 * @return mixed
	 */
	public function get_pretty($field_name){
		$field_value = $this->get($field_name);
		$field_obj = $this->_get_model()->field_settings_for($field_name);
		return  $field_obj->prepare_for_pretty_echoing($field_value);
	}
	
	/**
	 * Deletes this model object. That may mean just 'soft deleting' it though.
	 * @return boolean success
	 */
	public function delete(){
		$model=$this->_get_model();
		$result=$model->delete_by_ID($this->ID());
		if($result){
			return true;
		}else{
			return false;
		}
	}
	
	
	
	/**
	*		Saves this object to teh database. An array may be supplied to set some values on this
	 * object just before saving.
	* 
	* 		@access		public
	* 		@param		array		$set_cols_n_values		
	*		@return int, 1 on a successful update, the ID of
	*					the new entry on insert; 0 on failure	
	*/	
	public function save($set_cols_n_values=array()) {
		//set attributes as provided in $set_cols_n_values
		foreach($set_cols_n_values as $column=>$value){
			$this->set($column,$value);
		}
		//now get current attribute values
		$save_cols_n_values = array();
		foreach($this->_get_model()->field_settings() as $fieldName=>$field_obj){
			$attributeName=$this->_get_private_attribute_name($fieldName);
			$save_cols_n_values[$fieldName] = $this->$attributeName;
	
		}
		//if the object already has an ID, update it. Otherwise, insert it
		if ( !empty( $save_cols_n_values[$this->_get_primary_key_name()] ) ){
			$results = $this->_get_model()->update ( $save_cols_n_values, array(array($this->_get_primary_key_name()=>$this->ID())), true );
		} else {
			unset($save_cols_n_values[$this->_get_primary_key_name()]);
			
			$results = $this->_get_model()->insert ( $save_cols_n_values, true);
			if($results){//if successful, set the primary key
				$this->set($this->_get_primary_key_name(),$results);//for some reason the new ID is returned as part of an array,
				//where teh only key is 'new-ID', and it's value is the new ID.
			}
		}
		
		return $results;
	}
	
	
	/**
	 * converts a field name to the private attribute's name on teh class.
	 * Eg, converts "ANS_ID" to "_ANS_ID", which can be used like so $attr="_ANS_ID"; $this->$attr;
	 * @param string $fieldName
	 * @return string
	 */
	protected function _get_private_attribute_name($fieldName){
		return "_".$fieldName;
	}
	/**
	 * Gets the EEM_*_Model for this class
	 * @access public now, as this is more convenient 
	 * @return EEM_Base
	 */
	public function  _get_model(){
		//find model for this class
		$modelName=$this->_get_model_classname();
		return $this->_get_model_instance_with_name($modelName);
	}
	/**
	 * Gets the model instance (eg instance of EEM_Attendee) given its classname (eg EE_Attendee)
	 * @return EEM_Base
	 */
	protected function _get_model_instance_with_name($model_classname){
		$model=call_user_func($model_classname."::instance");
		return $model;
	}
	/**
	 * If no model name is provided, gets the model classname (eg EEM_Attendee) for this model object.
	 * If a model name is provided (eg Registration), gets the model classname for that model.
	 * @return string
	 */
	private function _get_model_classname( $model_name = null){
		if($model_name){
			$className = "EE_".$model_name;
		}else{
			$className=get_class($this);
		}
		$modelName=str_replace("EE_","EEM_",$className);
		return $modelName;
	}
	
	/**
	 * returns the name of the primary key attribute
	 * @return string
	 */
	protected function _get_primary_key_name(){
		return $this->_get_model()->get_primary_key_field()->get_name();
	}
	/**
	 * Gets the value of the primary key.
	 * @return mixed, if the primary key is of type INT it'll be an int. Otherwise it could be a string
	 */
	public function ID(){
		//get the name of teh primary key for this class' model, then find what php class attribute's name
		$pk_field_parameter = $this->_get_private_attribute_name($this->_get_primary_key_name());
		//now that we know the name of the variable, use a variable variable to get its value and return its 
		return $this->$pk_field_parameter;
	}
	
	/**
	 * Adds a relationship to the specified EE_Base_Class object, given the relationship's name. Eg, if the curren tmodel is related
	 * to a group of events, the $relationName should be 'Events', and should be a key in the EE Model's $_model_relations array
	 * @param mixed $otherObjectModelObjectOrID EE_Base_Class or the ID of the other object
	 * @param string $relationName eg 'Events','Question',etc.
	 * an attendee to a group, you also want to specify which role they will have in that group. So you would use this parameter to specificy array('role-column-name'=>'role-id')
	 
	 * @return boolean success
	 */
	public function _add_relation_to($otherObjectModelObjectOrID,$relationName){
		$otherObjectModelObjectOrID = $this->ensure_related_thing_is_model_obj($otherObjectModelObjectOrID,$relationName);
		$this->_get_model()->add_relationship_to($this, $otherObjectModelObjectOrID, $relationName);
		
		$this->cache( $relationName, $otherObjectModelObjectOrID );
	}
	
	
	
	/**
	 * Removes a relationship to the psecified EE_Base_Class object, given the relationships' name. Eg, if the curren tmodel is related
	 * to a group of events, the $relationName should be 'Events', and should be a key in the EE Model's $_model_relations array
	 * @param mixed $otherObjectModelObjectOrID EE_Base_Class or the ID of the other object
	 * @param string $relationName
	 * @return boolean success
	 */
	public function _remove_relation_to($otherObjectModelObjectOrID,$relationName){
		$otherObjectModelObjectOrID = $this->ensure_related_thing_is_model_obj($otherObjectModelObjectOrID, $relationName);
		$this->_get_model()->remove_relationship_to($this, $otherObjectModelObjectOrID, $relationName);
		$this->clear_cache($relationName, $otherObjectModelObjectOrID);
	}
	
	/**
	 * Gets all the related model objects of the specified type. Eg, if the current class if
	 * EE_Event, you could call $this->get_many_related('Registration') to get an array of all the
	 * EE_Registration objects which related to this event.
	 * @param string $relationName key in the model's _model_relations array
	 * @param array $query_paramslike EEM_Base::get_all
	 * @return EE_Base_Class[]
	 */
	public function get_many_related($relationName,$query_params = array()){
		//if there are query parameters, forget about caching the related model objects.
		if( $query_params ){
			$related_model_objects = $this->_get_model()->get_all_related($this, $relationName, $query_params);
		}else{
			//did we already cache the result of this query?
			$cached_results = $this->get_all_from_cache($relationName);
			if ( ! $cached_results ){
				$related_model_objects = $this->_get_model()->get_all_related($this, $relationName, $query_params);
				//if no query parameters were passed, then we got all the related model objects
				//for that relation. We can cache them then.
				foreach($related_model_objects as $related_model_object){
					$this->cache($relationName, $related_model_object);
				}
			}else{
				$related_model_objects = $cached_results;
			}
		}
		return $related_model_objects;
	}
	
	/**
	 * Gets the first (ie, one) related model object of the specified type.
	 * @param string $relationName key in the model's _model_relations array
	 * @param array $query_paramslike EEM_Base::get_all
	 * @return EE_Base_Class (not an array, a single object)
	 */
	public function get_first_related($relationName,$query_params = array()){
		if ($query_params){
			$related_model_object =  $this->_get_model()->get_first_related($this, $relationName, $query_params);
		}else{
			//first, check if we've already cached the result of this query
			$cached_result = $this->get_one_from_cache($relationName);
			if ( ! $cached_result ){
				$related_model_object = $this->_get_model()->get_first_related($this, $relationName, $query_params);
				$this->cache($relationName,$related_model_object);
			}else{
				$related_model_object = $cached_result;
			}
		}
		return $related_model_object;
	}
	
	/**
	 * Very handy general function to allow for plugins to extend any child of EE_Base_Class.
	 * If a method is called on a child of EE_Base_Class that doesn't exist, this function is called (http://www.garfieldtech.com/blog/php-magic-call)
	 * and passed the method's name and arguments.
	 * Instead of requiring a plugin to extend the EE_Base_Class (which works fine is there's only 1 plugin, but when will that happen?)
	 * they can add a hook onto 'filters_hook_espresso__{className}__{methodName}' (eg, filters_hook_espresso__EE_Answer__my_great_function)
	 * and accepts 2 arguments: the object on which teh function was called, and an array of the original arguments passed to the function. Whatever their callbackfunction returns will be returned by this function.
	 * Example: in functions.php (or in a plugin):
	 * add_filter('filter_hook_espresso__EE_Answer__my_callback','my_callback',10,3);
	 * function my_callback($previousReturnValue,EE_Base_Class $object,$argsArray){
			$returnString= "you called my_callback! and passed args:".implode(",",$argsArray);
	 *		return $previousReturnValue.$returnString;
	 * }
	 * require('EE_Answer.class.php');
	 * $answer=new EE_Answer(2,3,'The answer is 42');
	 * echo $answer->my_callback('monkeys',100);
	 * //will output "you called my_callback! and passed args:monkeys,100"
	 * @param string $methodName name of method which was called on a child of EE_Base_Class, but which 
	 * @param array $args array of original arguments passed to the function
	 * @return mixed whatever the plugin which calls add_filter decides
	 */
	public function __call($methodName,$args){
		$className=get_class($this);
		$tagName="filter_hook_espresso__{$className}__{$methodName}";
		if(!has_filter($tagName)){
			throw new EE_Error(sprintf(__("Method %s on class %s does not exist! You can create one with the following code in functions.php or in a plugin: add_filter('%s','my_callback',10,3);function my_callback(\$previousReturnValue,EE_Base_Class \$object, \$argsArray){/*function body*/return \$whatever;}","event_espresso"),
										$methodName,$className,$tagName));
		}
		return apply_filters($tagName,null,$this,$args);
	}
	
	
}