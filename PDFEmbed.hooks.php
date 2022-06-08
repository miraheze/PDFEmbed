<?php

class PDFEmbed {
	/**
	 * Sets up this extensions parser functions.
	 *
	 * @param Parser $parser
	 */
	public static function onParserFirstCallInit( Parser $parser ) {
		$parser->setHook( 'pdf', [ __CLASS__, 'generateTag' ] );
	}

	/**
	 * Generates the PDF object tag.
	 *
	 * @param string $file Namespace prefixed article of the PDF file to display.
	 * @param array	$args Arguments on the tag.
	 * @param Parser $parser
	 * @param PPFrame $frame
	 * @return string HTML
	 */
	public static function generateTag( $file, $args, Parser $parser, PPFrame $frame ) {
		global $wgPdfEmbed, $wgRequest;
		$parser->getOutput()->updateCacheExpiry( 0 );

		if ( strstr( $file, '{{{' ) !== false ) {
			$file = $parser->recursiveTagParse( $file, $frame );
		}

		$context = RequestContext::getMain();
		$request = $context->getRequest();

		if ( $request->getVal( 'action' ) == 'edit' || $request->getVal( 'action' ) == 'submit' ) {
			$user = $context->getUser();
		} else {
			$user = User::newFromName( $parser->getRevisionUser() );
		}

		if ( !$user ) {
			return self::error( 'embed_pdf_invalid_user' );
		}

		if ( !$user->isAllowed( 'embed_pdf' ) ) {
			return self::error( 'embed_pdf_no_permission' );
		}

		if ( empty( $file ) || !preg_match( '#(.+?)\.pdf#is', $file ) ) {
			return self::error( 'embed_pdf_blank_file' );
		}

		$file = wfFindFile( Title::newFromText( $file ) );

		if ( array_key_exists( 'width', $args ) ) {
			$width = intval( $parser->recursiveTagParse( $args['width'], $frame ) );
		} else {
			$width = intval( $wgPdfEmbed['width'] );
		}

		if ( array_key_exists( 'height', $args ) ) {
			$height = intval( $parser->recursiveTagParse( $args['height'], $frame ) );
		} else {
			$height = intval( $wgPdfEmbed['height'] );
		}

		if ( array_key_exists( 'page', $args ) ) {
			$page = intval( $parser->recursiveTagParse( $args['page'], $frame ) );
		} else {
			$page = 1;
		}

		if ( $file !== false ) {
			return self::embed( $file, $width, $height, $page );
		} else {
			return self::error( 'embed_pdf_invalid_file' );
		}
	}

	/**
	 * Returns a HTML object as string.
	 *
	 * @param File $file
	 * @param int $width Width of the object.
	 * @param int $height Height of the object.
	 * @param string $page
	 * @return string HTML object.
	 */
	private static function embed( File $file, $width, $height, $page ) {
		return Html::rawElement(
			'iframe',
			[
				'width' => $width,
				'height' => $height,
				'src' => $file->getFullUrl() . '#page=' . $page,
				'style' => 'max-width: 100%;',
				'loading' => 'lazy',
			]
		);
	}

	/**
	 * Returns a standard error message.
	 *
	 * @param string $messageKey Error message key to display.
	 * @return string HTML error message.
	 */
	private static function error( $messageKey ) {
		return Xml::span( wfMessage( $messageKey )->parse(), 'error' );
	}
}
