<?php
/**
 * @file
 * @ingroup PF
 */

/**
 * Adds and handles the 'pfautocomplete' action to the MediaWiki API.
 *
 * @ingroup PF
 *
 * @author Yaron Koren
 */
class UserTaggingAPI extends ApiBase {

	public function __construct( $query, $moduleName ) {
		parent::__construct( $query, $moduleName );
	}

	public function execute() {
		$params = $this->extractRequestParams();
		$substr = $params['substr'];
		$data = self::getAllUserPagesMatchingSubstring( $substr );

		// If we got back an error message, exit with that message.
		if ( !is_array( $data ) ) {
			if ( !$data instanceof Message ) {
				$data = ApiMessage::create( new RawMessage( '$1', array( $data ) ), 'unknownerror' );
			}
			$this->dieWithError( $data );
		}

		$formattedData = array();
		foreach ( $data as $index => $value ) {
			$title = Title::makeTitleSafe( NS_USER, $value );
			$linkWikitext = '[[' . $title->getPrefixedText() . '|' . $value . ']]';
			$formattedData[] = array( 'username' => $value, 'wikitext' => $linkWikitext );
		}

		// Set top-level elements.
		$result = $this->getResult();
		$result->setIndexedTagName( $formattedData, 'p' );
		$result->addValue( null, $this->getModuleName(), $formattedData );
	}

	protected function getAllowedParams() {
		return array(
			'limit' => array(
				ApiBase::PARAM_TYPE => 'limit',
				ApiBase::PARAM_DFLT => 10,
				ApiBase::PARAM_MIN => 1,
				ApiBase::PARAM_MAX => ApiBase::LIMIT_BIG1,
				ApiBase::PARAM_MAX2 => ApiBase::LIMIT_BIG2
			),
			'substr' => null,
		);
	}

	protected function getParamDescription() {
		return array(
			'substr' => 'Search substring',
			// 'limit' => 'Limit how many entries to return',
		);
	}

	protected function getDescription() {
		return 'Autocompletion call used by the Page Forms extension (https://www.mediawiki.org/Extension:Page_Forms)';
	}

	protected function getExamples() {
		return array(
			'api.php?action=usertaggingautocomplete&substr=te'
		);
	}

	public function getVersion() {
		return __CLASS__ . ': $Id$';
	}

	public static function getAllUserPagesMatchingSubstring( $substring = null ) {
                $db = wfGetDB( DB_SLAVE );
                $tables = array( 'page' );
                $columns = array( 'page_title' );
                $conditions = array();
                $conditions['page_namespace'] = NS_USER;

                if ( $db->getType() == 'mysql' ) {
                        $column_value = "LOWER(CONVERT(page_title USING utf8))";
                } else {
                        $column_value = "LOWER(page_title)";
                }
                $substring = strtolower( $substring );
                $substring = str_replace( ' ', '_', $substring );
                $conditions[] = $column_value . $db->buildLike( $substring, $db->anyString() );
		$options = array(
			'ORDER' => 'page_title',
			'LIMIT' => 20
		);

                $res = $db->select(
                        $tables,
                        $columns,
                        $conditions,
                        __METHOD__,
                        $options = array(),
                        $join = array() );

                $pages = array();
                while ( $row = $db->fetchRow( $res ) ) {
                        $title = str_replace( '_', ' ', $row[0] );
                        $pages[] = $title;
                }
                $db->freeResult( $res );

                return $pages;
	}

}
