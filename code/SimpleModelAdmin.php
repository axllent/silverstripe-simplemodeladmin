<?php
/**
 * This is a modified / simplified version of the default SilverStripe 3.0* ModelAdmin
 */
abstract class SimpleModelAdmin extends LeftAndMain {

	static $url_rule = '/$ModelClass/$Action';

	public static $managed_models = null;

	public static $allowed_actions = array();

	public static $url_handlers = array(
		'$ModelClass/$Action' => 'handleAction'
	);

	/**
	 * @var String
	 */
	protected $modelClass;


	/**
	 * Amount of results showing on a single page.
	 *
	 * @var int
	 */
	public static $page_length = 30;

	/**
	 * Disable column sorting by default
	 * @var true | false;
	 */
	public static $enable_sorting = false;

	/**
	 * Initialize the model admin interface. Sets up embedded jquery libraries and requisite plugins.
	 */
	public function init() {
		parent::init();

		$models = $this->getManagedModels();

		if($this->request->param('ModelClass')) {
			$this->modelClass = $this->unsanitiseClassName($this->request->param('ModelClass'));
		} else {
			reset($models);
			$this->modelClass = key($models);
		}

		// security check for valid models
		if(!array_key_exists($this->modelClass, $models)) {
			user_error('ModelAdmin::init(): Invalid Model class', E_USER_ERROR);
		}
	}

	public function Link($action = null) {
		if(!$action) $action = $this->sanitiseClassName($this->modelClass);
		return parent::Link($action);
	}

	public function getEditForm($id = null, $fields = null) {
		$list = $this->getList();
		$listField = GridField::create(
			$this->sanitiseClassName($this->modelClass),
			false,
			$list,
			$fieldConfig = GridFieldConfig_RecordEditor::create($this->stat('page_length'))
				->removeComponentsByType('GridFieldFilterHeader')
		);

		if (!$this->stat('enable_sorting')) {
			$summary_fields = Config::inst()->get($this->modelClass, 'summary_fields');
			$sorting = array();
			foreach ($summary_fields as $col)
				$sorting[$col] = 'FieldNameNoSorting';
			$fieldConfig->getComponentByType('GridFieldSortableHeader')->setFieldSorting($sorting);
		}


		// Validation
		if(singleton($this->modelClass)->hasMethod('getCMSValidator')) {
			$detailValidator = singleton($this->modelClass)->getCMSValidator();
			$listField->getConfig()->getComponentByType('GridFieldDetailForm')->setValidator($detailValidator);
		}

		$form = new Form(
			$this,
			'EditForm',
			new FieldList($listField),
			new FieldList()
		);
		$form->addExtraClass('cms-edit-form cms-panel-padded center');
		$form->setTemplate($this->getTemplatesWithSuffix('_EditForm'));
		$editFormAction = Controller::join_links($this->Link($this->sanitiseClassName($this->modelClass)), 'EditForm');
		$form->setFormAction($editFormAction);
		$form->setAttribute('data-pjax-fragment', 'CurrentForm');

		$this->extend('updateEditForm', $form);

		return $form;
	}

	/**
	 * @return SearchContext
	 */
	public function getSearchContext() {
		$context = singleton($this->modelClass)->getDefaultSearchContext();

		// Namespace fields, for easier detection if a search is present
		foreach($context->getFields() as $field) $field->setName(sprintf('q[%s]', $field->getName()));
		foreach($context->getFilters() as $filter) $filter->setFullName(sprintf('q[%s]', $filter->getFullName()));

		$this->extend('updateSearchContext', $context);

		return $context;
	}


	public function getList() {
		$context = $this->getSearchContext();
		$params = $this->request->requestVar('q');
		$list = $context->getResults('');

		$this->extend('updateList', $list);

		return $list;
	}


	/**
	 * Returns managed models' create, search, and import forms
	 * @uses SearchContext
	 * @uses SearchFilter
	 * @return SS_List of forms
	 */
	protected function getManagedModelTabs() {
		$models = $this->getManagedModels();
		$forms  = new ArrayList();

		foreach($models as $class => $options) {
			$forms->push(new ArrayData(array (
				'Title'     => $options['title'],
				'ClassName' => $class,
				'Link' => $this->Link($this->sanitiseClassName($class)),
				'LinkOrCurrent' => ($class == $this->modelClass) ? 'current' : 'link'
			)));
		}

		return $forms;
	}

	/**
	 * Sanitise a model class' name for inclusion in a link
	 * @return string
	 */
	protected function sanitiseClassName($class) {
		return str_replace('\\', '-', $class);
	}

	/**
	 * Unsanitise a model class' name from a URL param
	 * @return string
	 */
	protected function unsanitiseClassName($class) {
		return str_replace('-', '\\', $class);
	}

	/**
	 * @return array Map of class name to an array of 'title' (see {@link $managed_models})
	 */
	public function getManagedModels() {
		$models = $this->stat('managed_models');
		if(is_string($models)) {
			$models = array($models);
		}
		if(!count($models)) {
			user_error(
				'ModelAdmin::getManagedModels():
				You need to specify at least one DataObject subclass in public static $managed_models.
				Make sure that this property is defined, and that its visibility is set to "public"',
				E_USER_ERROR
			);
		}

		// Normalize models to have their model class in array key
		foreach($models as $k => $v) {
			if(is_numeric($k)) {
				$models[$v] = array('title' => singleton($v)->i18n_singular_name());
				unset($models[$k]);
			}
		}

		return $models;
	}

	/**
	 * @return ArrayList
	 */
	public function Breadcrumbs($unlinked = false) {
		$items = parent::Breadcrumbs($unlinked);

		// Show the class name rather than ModelAdmin title as root node
		$models = $this->getManagedModels();
		$params = $this->request->getVars();
		if(isset($params['url'])) unset($params['url']);

		$items[0]->Title = $models[$this->modelClass]['title'];
		$items[0]->Link = Controller::join_links(
			$this->Link($this->sanitiseClassName($this->modelClass)),
			'?' . http_build_query($params)
		);

		return $items;
	}

	/**
	 * overwrite the static page_length of the admin panel,
	 * should be called in the project _config file.
	 */
	public static function set_page_length($length){
		self::$page_length = $length;
	}

	/**
	 * Return the static page_length of the admin, default as 30
	 */
	public static function get_page_length(){
		return self::$page_length;
	}

}
