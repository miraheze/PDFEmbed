<?php
/**
 * PDFEmbed
 * PDFEmbed Hooks
 *
 * @author		Alexia E. Smith
 * @license		LGPLv3 http://opensource.org/licenses/lgpl-3.0.html
 * @package		PDFEmbed
 * @link		http://www.mediawiki.org/wiki/Extension:PDFEmbed
 *
 **/

class PDFEmbed {
	/**
	 * Sets up this extensions parser functions.
	 *
	 * @access	public
	 * @param	object	Parser object passed as a reference.
	 * @return	boolean	true
	 */
	static public function onParserFirstCallInit(Parser &$parser) {
		$parser->setHook('pdf', 'PDFEmbed::generateTag');

		return true;
	}

	/**
	 * Generates the PDF object tag.
	 *
	 * @access	public
	 * @param	string	Namespace prefixed article of the PDF file to display.
	 * @param	array	Arguments on the tag.
	 * @param	object	Parser object.
	 * @param	object	PPFrame object.
	 * @return	string	HTML
	 */
	static public function generateTag($file, $args = [], Parser $parser, PPFrame $frame) {
		global $wgPdfEmbed, $wgRequest, $wgUser;
		$parser->getOutput()->updateCacheExpiry( 0 );

		if (strstr($file, '{{{') !== false) {
			$file = $parser->recursiveTagParse($file, $frame);
		}

		if ($wgRequest->getVal('action') == 'edit' || $wgRequest->getVal('action') == 'submit') {
			$user = $wgUser;
		} else {
			$user = User::newFromName($parser->getRevisionUser());
		}

		if ($user === false) {
			return self::error('embed_pdf_invalid_user');
		}

		if (!$user->isAllowed('embed_pdf')) {
			return self::error('embed_pdf_no_permission');
		}

		if (empty($file) || !preg_match('#(.+?)\.pdf#is', $file)) {
			return self::error('embed_pdf_blank_file');
		}

		$file = wfFindFile(Title::newFromText($file));

		if (array_key_exists('width', $args)) {
			$width = intval($parser->recursiveTagParse($args['width'], $frame));
		} else {
			$width = intval($wgPdfEmbed['width']);
		}
		if (array_key_exists('height', $args)) {
			$height = intval($parser->recursiveTagParse($args['height'], $frame));
		} else {
			$height = intval($wgPdfEmbed['height']);
		}
		if (array_key_exists('page', $args)) {
			$page = intval($parser->recursiveTagParse($args['page'], $frame));
		} else {
			$page = 1;
		}

		if ($file !== false) {
			return self::embed($file, $width, $height, $page);
		} else {
			return self::error('embed_pdf_invalid_file');
		}
	}

	/**
	 * Returns a HTML object as string.
	 *
	 * @access	private
	 * @param	object	File object.
	 * @param	integer	Width of the object.
	 * @param	integer	Height of the object.
	 * @return	string	HTML object.
	 */
	static private function embed(File $file, $width, $height, $page) {
		return Html::rawElement(
			'iframe',
			[
				'width' => $width,
				'height' => $height,
				'src' => $file->getFullUrl().'#page='.$page,
				'style' => 'max-width: 100%;'
				'loading' => 'lazy',
			]
		);
	}

	/**
	 * Returns a standard error message.
	 *
	 * @access	private
	 * @param	string	Error message key to display.
	 * @return	string	HTML error message.
	 */
	static private function error($messageKey) {
		return Xml::span(wfMessage($messageKey)->plain(), 'error');
	}
}
