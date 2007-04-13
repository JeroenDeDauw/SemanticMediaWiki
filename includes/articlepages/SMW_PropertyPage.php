<?php
/**
 * Special handling for relation/attribute description pages.
 * Some code based on CategoryPage.php
 *
 * @author: Markus Krötzsch
 */

if( !defined( 'MEDIAWIKI' ) )   die( 1 );

global $smwgIP;
require_once( "$smwgIP/includes/articlepages/SMW_OrderedListPage.php");

/**
 * Implementation of MediaWiki's Article that shows additional information on
 * property pages (Relation: and Attribute:). Very simliar to CategoryPage, but
 * with different printout that also displays values for each subject with the
 * given property.
 */
class SMWPropertyPage extends SMWOrderedListPage {

	/**
	 * Use small $limit (property pages might become large)
	 */
	protected function initParameters() {
		global $smwgContLang, $smwgPropertyPagingLimit;
		$this->limit = $smwgPropertyPagingLimit;
		// Do not attempt listings for special properties:
		// they behave differently, have dedicated search UIs, and
		// might even be unsearchable by design
		$srels = $smwgContLang->getSpecialPropertiesArray();
		$special = array_search($this->mTitle->getText(), $srels);
		if ($special !== false) {
			return false;
		}
		return true;
	}

	/**
	 * Fill the internal arrays with the set of articles to be displayed (possibly plus one additional
	 * article that indicates further results).
	 */
	protected function doQuery() {
		global $wgContLang;
		$store = smwfGetStore();
		$options = new SMWRequestOptions();
		$options->limit = $this->limit + 1;
		$options->sort = true;
		$reverse = false;
		if ($this->from != '') {
			$options->boundary = $this->from;
			$options->ascending = true;
			$options->include_boundary = true;
		} elseif ($this->until != '') {
			$options->boundary = $this->until;
			$options->ascending = false;
			$options->include_boundary = false;
			$reverse = true;
		}
		if ( $this->mTitle->getNamespace()== SMW_NS_RELATION ) {
			$this->articles = $store->getAllRelationSubjects($this->mTitle, $options);
		} else {
			$this->articles = $store->getAllAttributeSubjects($this->mTitle, $options);
		}
		if ($reverse) {
			$this->articles = array_reverse($this->articles);
		}

		foreach ($this->articles as $title) {
			$this->articles_start_char[] = $wgContLang->convert( $wgContLang->firstChar( $title->getText() ) );
		}
	}

	/**
	 * Generates the headline for the page list and the HTML encoded list of pages which
	 * shall be shown.
	 */
	protected function getPages() {
		$ti = htmlspecialchars( $this->mTitle->getText() );
		$nav = $this->getNavigationLinks();
		$r = '<a name="SMWResults"></a>' . $nav . "<div id=\"mw-pages\">\n";
		switch ( $this->mTitle->getNamespace() ) {
			case SMW_NS_RELATION:
				$r .= '<h2>' . wfMsg('smw_relation_header',$ti) . "</h2>\n";
				$r .= wfMsg('smw_relationarticlecount', min($this->limit, count($this->articles))) . "\n";
				break;
			case SMW_NS_ATTRIBUTE:
				$r .= '<h2>' . wfMsg('smw_relation_header',$ti) . "</h2>\n";
				$r .= wfMsg('smw_relationarticlecount', min($this->limit, count($this->articles))) . "\n";
				break;
		}
		$r .= $this->shortList( $this->articles, $this->articles_start_char ) . "\n</div>" . $nav;
		return $r;
	}

	/**
	 * Format a list of articles chunked by letter in a table that shows subject articles in
	 * one column and object articles/values in the other one.
	 */
	private function shortList() {
		global $wgContLang;
		$store = smwfGetStore();

		$ac = count($this->articles);
		if ($ac > $this->limit) {
			if ($this->until != '') {
				$start = 1;
			} else {
				$start = 0;
				$ac = $ac - 1;
			}
		} else {
			$start = 0;
		}

		$r = '<table style="width: 100%; ">';
		$prevchar = 'None';
		for ($index = $start; $index < $ac; $index++ ) {
			// Header for index letters
			if ($this->articles_start_char[$index] != $prevchar) {
				$r .= '<tr><th class="smwattname"><h3>' . htmlspecialchars( $this->articles_start_char[$index] ) . "</h3></th><th></th></tr>\n";
				$prevchar = $this->articles_start_char[$index];
			}
			// Attribute/relation name
			$searchlink = SMWInfolink::newBrowsingLink('+',$this->articles[$index]->getPrefixedText());
			$r .= '<tr><td class="smwattname">' . $this->getSkin()->makeKnownLinkObj( $this->articles[$index], 
			  $wgContLang->convert( $this->articles[$index]->getPrefixedText() ) ) . 
			  '&nbsp;' . $searchlink->getHTML($this->getSkin()) .
			  '</td><td class="smwatts">';
			// Attribute/relation values
			$ropts = new SMWRequestOptions();
			$ropts->limit = 4;
			if ($this->mTitle->getNamespace() == SMW_NS_RELATION) {
				$objects = $store->getRelationObjects($this->articles[$index], $this->mTitle,$ropts);
				$i=0;
				foreach ($objects as $object) {
					if ($i != 0) {
						$r .= ', ';
					}
					$i++;
					if ($i < 4) {
						$searchlink = SMWInfolink::newRelationSearchLink('+',$this->mTitle->getText(),$object->getPrefixedText());
						$r .= $this->getSkin()->makeLinkObj($object, $wgContLang->convert( $object->getText() )) . '&nbsp;&nbsp;' . $searchlink->getHTML($this->getSkin());
					} else {
						$searchlink = SMWInfolink::newInverseRelationSearchLink('&hellip;', $this->articles[$index]->getPrefixedText(), $this->mTitle->getText());
						$r .= $searchlink->getHTML($this->getSkin());
					}
				}
			} elseif ($this->mTitle->getNamespace() == SMW_NS_ATTRIBUTE) {
				$values = $store->getAttributeValues($this->articles[$index], $this->mTitle, $ropts);
				$i=0;
				foreach ($values as $value) {
					if ($i != 0) {
						$r .= ', ';
					}
					$i++;
					if ($i < 4) {
						$r .= $value->getValueDescription();
						$sep = '&nbsp;&nbsp;';
						foreach ($value->getInfolinks() as $link) {
							$r .= $sep . $link->getHTML($this->getSkin());
							$sep = ' &nbsp;&nbsp;'; // allow breaking for longer lists of infolinks
						}
					} else {
						$searchlink = SMWInfolink::newInverseAttributeSearchLink('&hellip;', $this->articles[$index]->getPrefixedText(), $this->mTitle->getText());
						$r .= $searchlink->getHTML($this->getSkin());
					}
				}
			}
			$r .= "</td></tr>\n";
		}
		$r .= '</table>';
		return $r;
	}

}

?>
