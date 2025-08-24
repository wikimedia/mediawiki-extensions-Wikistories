<?php

namespace MediaWiki\Extension\Wikistories;

use MediaWiki\Context\RequestContext;
use MediaWiki\Title\Title;

class StoryTrackingCategories {

	public const TC_NO_IMAGE = 'wikistories-no-image-category';

	public const TC_NO_ARTICLE = 'wikistories-no-related-article';

	public const TC_OUTDATED_TEXT = 'wikistories-outdated-text-category';

	/**
	 * @var string[] ALL_TEXT_FORMS
	 */
	private static $ALL_TEXT_FORMS = [];

	private readonly RequestContext $context;

	public function __construct() {
		$this->context = RequestContext::getMain();
	}

	/**
	 * get text form from all the tracking categories in WikiStory
	 *
	 * @return array
	 */
	private function getAllTextForms(): array {
		if ( self::$ALL_TEXT_FORMS !== [] ) {
			return self::$ALL_TEXT_FORMS;
		}

		$context = $this->context;
		return array_map( static function ( $key ) use ( $context ) {
			$textForm = $context->msg( $key )->inContentLanguage()->text();
			self::$ALL_TEXT_FORMS[ $key ] = $textForm;
			return $textForm;
		}, [ self::TC_NO_IMAGE, self::TC_NO_ARTICLE, self::TC_OUTDATED_TEXT ] );
	}

	/**
	 * get text form from the category page
	 *
	 * @param string $key
	 * @return string
	 */
	private function getTextForm( string $key ): string {
		if ( !isset( self::$ALL_TEXT_FORMS[ $key ] ) ) {
			$this->getAllTextForms();
		}

		return self::$ALL_TEXT_FORMS[ $key ];
	}

	/**
	 * @param array $categories
	 * @param Title $title
	 * @return bool
	 */
	public function hasDiff( $categories, Title $title ): bool {
		// find the existing wiki story tracking categories in this story title
		$parentCategories = array_intersect(
			array_map( static function ( $key ) {
				$categoryName = explode( ':', $key, 2 )[ 1 ];
				return str_replace( '_', ' ', $categoryName );
			}, array_keys( $title->getParentCategories() ) ),
			$this->getAllTextForms()
		);

		// in most cases, the diff is either addtional or missing one category
		if ( count( $parentCategories ) !== count( $categories ) ) {
			return true;
		}

		// get text form from the category page
		$trackingCategoriesTexts = array_map( function ( $trackingCategory ) {
			return self::getTextForm( $trackingCategory );
		}, $categories );

		// check diff
		return array_diff( $trackingCategoriesTexts, $parentCategories ) ||
			array_diff( $parentCategories, $trackingCategoriesTexts );
	}
}
