<?php if (!defined('EVENT_ESPRESSO_VERSION')) exit('No direct script access allowed');
do_action('action_hook_espresso_log', __FILE__, ' FILE LOADED', '' );
/**
 *
 * Event Espresso
 *
 * Event Registration and Management Plugin for WordPress
 *
 * @ package			Event Espresso
 * @ author				Seth Shoultes
 * @ copyright		(c) 2008-2011 Event Espresso  All Rights Reserved.
 * @ license			http://eventespresso.com/support/terms-conditions/   * see Plugin Licensing *
 * @ link				http://www.eventespresso.com
 * @ version		 	4.0
 *
 * ------------------------------------------------------------------------
 * 
 * EE_Base_Class class
 *
 * @package				Event Espresso
 * @subpackage			includes/classes/EE_Base_Class.class.php
 * @author				Michael Nelson 
 *
 * ------------------------------------------------------------------------
 */

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
	 * @param string $timezone indicate what timezone you want any datetime fields to be in when instantiating a EE_Base_Class object.
	 * 
	 */
	protected function __construct($fieldValues=null, $bydb = FALSE, $timezone = NULL ){
		$className=get_class($this);
		do_action("action_hook_espresso__{$className}__construct",$this,$fieldValues);
		$model=$this->get_model();
		
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
		$field_obj = $this->get_model()->field_settings_for($field_name);
		 $holder_of_value = $field_obj->prepare_for_set($field_value);
		 if( ($holder_of_value === NULL || $holder_of_value ==='') && $use_default){
			 $this->$privateAttributeName = $field_obj->get_default_value();
		 }else{
			$this->$privateAttributeName = $holder_of_value; 
		 }

		 //let's unset any cache for this field_name from the $_cached_properties property.
		 $this->_clear_cached_property( $privateAttributeName );
		 
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
		$relationship_to_model = $this->get_model()->related_settings_for($relationName);
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

		if ( isset( $this->_cached_properties[$propertyname] ) ) {
			return $this->_cached_properties[$propertyname];
		}

		//otherwise let's return the property
		$field_name = ltrim( $propertyname, '_' );
		$field_obj = $this->get_model()->field_settings_for($field_name);
		$value = $field_obj->prepare_for_get($this->$propertyname );
		$this->_set_cached_property( $propertyname, $value );
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
	 * This just clears out ONE property if it exists in the cache
	 * @param  string $propertyname the property to remove if it exists (from the _cached_properties array)
	 * @return void               
	 */
	protected function _clear_cached_property( $propertyname ) {
		if ( isset( $this->_cached_properties[$propertyname] ) )
			unset( $this->_cached_properteis[$propertyname] );
	}


	
	/**
	 * Ensures that this related thing is a model object.
	 * @param mixed $object_or_id EE_base_Class/int/string either a rellate dmodel object, or its ID
	 * @param string $model_name name of the related thing, eg 'Attendee',
	 * @return EE_Base_Class
	 */
	protected function ensure_related_thing_is_model_obj($object_or_id,$model_name){
		$other_model_instance = self::_get_model_instance_with_name(self::_get_model_classname($model_name));
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
		$relationship_to_model = $this->get_model()->related_settings_for($relationName);
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
		$field_obj = $this->get_model()->field_settings_for($field_name);
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
		$field_obj = $this->get_model()->field_settings_for($field_name);
		return  $field_obj->prepare_for_pretty_echoing($field_value);
	}
	
	/**
	 * Deletes this model object. That may mean just 'soft deleting' it though.
	 * @return boolean success
	 */
	public function delete(){
		$model=$this->get_model();
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
		foreach($this->get_model()->field_settings() as $fieldName=>$field_obj){
			$attributeName=$this->_get_private_attribute_name($fieldName);
			$save_cols_n_values[$fieldName] = $this->$attributeName;
	
		}
		//if the object already has an ID, update it. Otherwise, insert it
		if ( !empty( $save_cols_n_values[self::_get_primary_key_name()] ) ){
			$results = $this->_get_model()->update ( $save_cols_n_values, array(array(self::_get_primary_key_name()=>$this->ID())), true );
		} else {
			unset($save_cols_n_values[self::_get_primary_key_name()]);
			
			$results = $this->get_model()->insert ( $save_cols_n_values, true);
			if($results){//if successful, set the primary key
				$this->set(self::_get_primary_key_name(),$results);//for some reason the new ID is returned as part of an array,
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
	 * for getting a model while instantiated.
	 * @return EEM_Base model object
	 */
	public function get_model() {
		$modelName = self::_get_model_classname();
		return self::_get_model_instance_with_name($modelName);
	}


	/**
	 * Gets the EEM_*_Model for this class
	 * @access public now, as this is more convenient 
	 * @return EEM_Base
	 */
	protected static function  _get_model( $classname ){
		//find model for this class
		$modelName=self::_get_model_classname($classname);
		return self::_get_model_instance_with_name($modelName);
	}



	/**
	 * Gets the model instance (eg instance of EEM_Attendee) given its classname (eg EE_Attendee)
	 * @return EEM_Base
	 */
	protected static function _get_model_instance_with_name($model_classname){
		$model=call_user_func($model_classname."::instance");
		return $model;
	}


	/**
	 * If no model name is provided, gets the model classname (eg EEM_Attendee) for this model object.
	 * If a model name is provided (eg Registration), gets the model classname for that model.
	 * @return string
	 */
	private static function _get_model_classname( $model_name = null){
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
	protected static function _get_primary_key_name( $classname = NULL ){
		return self::_get_model( $classname )->get_primary_key_field()->get_name();
	}
	/**
	 * Gets the value of the primary key.
	 * @return mixed, if the primary key is of type INT it'll be an int. Otherwise it could be a string
	 */
	public function ID(){
		//get the name of teh primary key for this class' model, then find what php class attribute's name
		$pk_field_parameter = $this->_get_private_attribute_name(self::_get_primary_key_name());
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
		$this->get_model()->add_relationship_to($this, $otherObjectModelObjectOrID, $relationName);
		
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
		$this->get_model()->remove_relationship_to($this, $otherObjectModelObjectOrID, $relationName);
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
			$related_model_objects = $this->get_model()->get_all_related($this, $relationName, $query_params);
		}else{
			//did we already cache the result of this query?
			$cached_results = $this->get_all_from_cache($relationName);
			if ( ! $cached_results ){
				$related_model_objects = $this->get_model()->get_all_related($this, $relationName, $query_params);
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
			$related_model_object =  $this->get_model()->get_first_related($this, $relationName, $query_params);
		}else{
			//first, check if we've already cached the result of this query
			$cached_result = $this->get_one_from_cache($relationName);
			if ( ! $cached_result ){
				$related_model_object = $this->get_model()->get_first_related($this, $relationName, $query_params);
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