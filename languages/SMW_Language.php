<?php
/**
 * @file
 * @ingroup SMWLanguage
 */

/**
 * This group contains all parts of SMW that relate to localisation and
 * translation.
 * @defgroup SMWLanguage SMWLanguage
 * @ingroup SMW
 */

/**
 * Base class for all SMW language classes.
 * @author Markus Krötzsch
 * @ingroup SMWLanguage
 * @ingroup Language
 */
abstract class SMWLanguage {

	// the special message arrays ...
	protected $m_DatatypeLabels;
	protected $m_DatatypeAliases = array();
	protected $m_SpecialProperties;
	protected $m_SpecialPropertyAliases = array();
	protected $m_Namespaces;
	protected $m_NamespaceAliases = array();
	/// Twelve strings naming the months. English is always supported in Type:Date, so
	/// the default is simply empty (no labels in addition to English)
	protected $m_months = array();
	/// Twelve strings briefly naming the months. English is always supported in Type:Date, so
	/// the default is simply empty (no labels in addition to English)
	protected $m_monthsshort = array();
	/// Preferred interpretations for dates with 1, 2, and 3 components. There is an array for
	/// each case, and the constants define the obvious order (e.g. SMW_YDM means "first Year, 
	/// then Day, then Month). Unlisted combinations will not be accepted at all.
	protected $m_dateformats = array(array(SMW_Y), array(SMW_MY,SMW_YM), array(SMW_DMY,SMW_MDY,SMW_YMD,SMW_YDM));


	/**
	 * Function that returns an array of namespace identifiers.
	 */
	function getNamespaces() {
		return $this->m_Namespaces;
	}

	/**
	 * Function that returns an array of namespace aliases, if any.
	 */
	function getNamespaceAliases() {
		return $this->m_NamespaceAliases;
	}

	/**
	 * Return all labels that are available as names for built-in datatypes. Those
	 * are the types that users can access via [[has type::...]] (more built-in 
	 * types may exist for internal purposes but the user won't need to
	 * know this). The returned array is indexed by (internal) type ids.
	 */
	function getDatatypeLabels() {
		return $this->m_DatatypeLabels;
	}

	/**
	 * Return an array that maps aliases to internal type ids. All ids used here
	 * should also have a primary label defined in m_DatatypeLabels.
	 */
	function getDatatypeAliases() {
		return $this->m_DatatypeAliases;
	}

	/**
	 * Function that returns the labels for the special properties.
	 */
	function getSpecialPropertiesArray() {
		return $this->m_SpecialProperties;
	}

	/**
	 * Aliases for special properties, if any.
	 */
	function getSpecialPropertyAliases() {
		return $this->m_SpecialPropertyAliases;
	}

	/**
	 * Function that returns the preferred date formats
	 */
	function getDateFormats() {
		return $this->m_dateformats;
	}

	/**
	 * Find and return the id for the special property of the given local label.
	 * If the label does not belong to a special property, return false.
	 * The given label should be slightly normalised, i.e. as returned by Title
	 * or smwfNormalTitleText().
	 */
	function findSpecialPropertyID($label, $useAlias = true) {
		$id = array_search($label, $this->m_SpecialProperties);
		if ($id !== false) {
			return $id;
		} elseif ( ($useAlias) && (array_key_exists($label, $this->m_SpecialPropertyAliases)) ) {
			return $this->m_SpecialPropertyAliases[$label];
		} else {
			return false;
		}
	}

	/**
	 * Function looks up a month and returns the corresponding number (e.g. No
	 */
	function findMonth($label){
		$id = array_search($label, $this->m_months);
		if ($id !== false) {
			return $id+1;
		}
		$id = array_search($label, $this->m_monthsshort);
		if ($id !== false) {
			return $id+1;
		}
		return false;
	}

	/**
	 * Get the translated user label for a given internal special property ID.
	 * Returns false for properties without a translation (these are usually the
	 * internal ones generated by SMW but not shown to the user).
	 */
	public function findSpecialPropertyLabel($id) {
		if (array_key_exists($id, $this->m_SpecialProperties)) {
			return $this->m_SpecialProperties[$id];
		} else { // incomplete translation (language bug) or deliberately invisible property
			return false;
		}
	}

	/**
	 * Extends the array of special properties with a mapping from an $id to a
	 * language dependent label.
	 * @note This function is provided for ad hoc compatibility with the Halo project.
	 * A better solution will replace it at some time.
	 */
	function addSpecialProperty($id, $label) {
		if (array_key_exists($id, $this->m_SpecialProperties)) {
			trigger_error('The ID "' . $id . '" already belongs to the special property "' . $this->m_SpecialProperties[$id] . '" and thus cannot be used for "' . $label . '".', E_USER_WARNING);
		} elseif ($id < 1000) {
			trigger_error('IDs below 1000 are not allowed for custom special properties. Registration of "' . $label . '" failed.', E_USER_WARNING);
		} else {
			$this->m_SpecialProperties[$id] = $label;
		}
	}

}


