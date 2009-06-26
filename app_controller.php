<?php
/**
 * Generic AppController
 *
 * @package		cake
 * @subpackage	cake.app
 * @author Marko Marković <marko+gac@ultimate.in.rs>
 * @licence The MIT License
 * @repository http://github.com/markomarkovic/generic_app_controller/
 */
class AppController extends Controller {

	// These helpers and components are going to be available in every controller.
	var $helpers = array('Html', 'Javascript', 'Form', 'Time', 'Text');
	var $components = array('RequestHandler', 'Cookie', 'Session');


	/**
	 * Generic functions
	 */

	function index() {
		$this->data = $this->paginate();

		// If the action is requested, we're just returning the data.
		if (isset($this->params['requested'])) {
			return $this->data;
		}

		// Setting the view variables.
		$this->set($this->_pluralName($this->modelClass), $this->data);
	}

	function view($id) {
		// If we're using Sluggable behaviour, we need to fetch the data according to the slug and not the id.
		if (isset($this->{$this->modelClass}->Behaviors->Sluggable)) {
			$slug = $this->{$this->modelClass}->Behaviors->Sluggable->__settings[$this->modelClass]['slug'];
			$this->data = $this->{$this->modelClass}->find(array($this->modelClass.'.'.$slug => $id));
		} else {
			$this->data = $this->{$this->modelClass}->read(null, $id);
		}

		if (isset($this->params['requested'])) {
			return $this->data;
		}

		// Not found.
		if (!isset($this->data[$this->modelClass])) {
			$this->Session->setFlash(sprintf(__("%s '%s' not found.", true), __($this->modelClass, true), $id));
			$this->redirect(array('action' => 'index'), 404);
		}

		// Setting the Title.
		if (array_key_exists('title', $this->data[$this->modelClass])) {
			$this->set('title', $this->data[$this->modelClass]['title']);
		}

		// Setting the view variables.
		$this->set(Inflector::variable($this->modelClass), $this->data);
	}


	/**
	 * Generic Admin functions
	 */

	function admin_index() {
		$this->data = $this->paginate();
		$this->set($this->_pluralName($this->modelClass), $this->data);
	}

	function admin_view($id = null) {
		if (!$id) {
			$this->Session->setFlash(sprintf(__('Invalid %s.', true), __($this->modelClass, true)));
			$this->redirect(array('action' => 'index'));
		}
		$this->data = $this->{$this->modelClass}->read(null,$id);
		$this->set(Inflector::variable($this->modelClass), $this->data);
	}

	function admin_add() {
		if (!empty($this->data)) {
			$this->{$this->modelClass}->create();
			$this->__tryToSaveData();
		}

		// Fetching and setting the associated models data
		$this->__setAssociatedData();
	}

	function admin_edit($id = null) {
		if (!$id && empty($this->data)) {
			$this->Session->setFlash(sprintf(__('Invalid %s.', true), __($this->modelClass, true)));
			$this->redirect(array('action' => 'index'));
		}

		// If it's empty, the Form isn't submitted yet.
		if (empty($this->data)) {
			$this->data = $this->{$this->modelClass}->read(null, $id);
		} else { // Form is submitted, trying to save the data
			$this->__tryToSaveData();
		}

		// Fetching and setting the associated models data
		$this->__setAssociatedData();
	}

	function admin_delete($id = null) {
		if (!$id) {
			$this->Session->setFlash(sprintf(__('Invalid %s.', true), __($this->modelClass, true)));
			$this->redirect(array('action' => 'index'));
		}
		if ($this->{$this->modelClass}->del($id)) {
			$this->Session->setFlash(sprintf(__('%s #%d deleted.', true), __($this->modelClass, true), $id));
			$this->redirect(array('action' => 'index'));
		}
	}


	/**
	 * Attempts to save the data.
	 *
	 * @access private
	 */
	function __tryToSaveData() {
		if ($this->{$this->modelClass}->save($this->data)) {
			$this->Session->setFlash(sprintf(__('The %s has been saved.', true), __($this->modelClass, true)));
			$this->redirect(array('action' => 'index'));
		} else {
			$this->Session->setFlash(sprintf(__('The %s could not be saved.', true), __($this->modelClass, true)).' '.__('Please, try again.', true));
		}
	}

	/**
	 * Sets all the Associated model data
	 *
	 * @access private
	 */
	function __setAssociatedData() {
		foreach(array('belongsTo', 'hasAndBelongsToMany') as $association) {
			foreach ($this->{$this->modelClass}->{$association} as $alias => $values) {
				$theList = $this->{$this->modelClass}->{$alias}->find('list');
				$this->set($this->_pluralName($alias), $theList);
			}
		}
	}

	/**
	 * Creates the plural name for views
	 *
	 * @param string $name Name to use
	 * @return string Plural name for views
	 * @access protected
	 */
	function _pluralName($name) {
		return Inflector::variable(Inflector::pluralize($name));
	}


}
