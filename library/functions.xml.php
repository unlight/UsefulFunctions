<?php
// …

if (!function_exists('Markdownify')) {
	function Markdownify($Html) {
		$Html = Gdn_Format::To($Html, 'xHtml');
		$Snoopy = Gdn::Factory('Snoopy');
		$Vars = array('input' => $Html, 'keepHTML' => 1);
		$Snoopy->Submit('http://milianw.de/projects/markdownify/demo.php', $Vars);
		$Doc = PqDocument($Snoopy->results);
		$Code = Pq('pre > code:eq(0)')->Text();
		$Result = $Code;
		return $Result;
	}
}

/**
* Converts HTML to Markdown (uses PhpQuery library)
*/ 

/*if (!function_exists('UnMarkdown')) {
	function UnMarkdown($Html) {
		$Result = '';
		$Doc = PqDocument($Html);
		$Blocks = array();
		foreach (Pq('p') as $Paragraph) {
			if ($Paragraph->HasAttributes()) continue;
			$Blocks[] = Pq($Paragraph)->Html();
			d(Pq($Paragraph)->HtmlOuter());
			//DOMNode->hasAttributes() - Checks if node has attributes
		}
		d($Html);
		// http://en.wikipedia.org/wiki/Markdown
		// 1. **strong emphasis** (more common) or __strong emphasis__ (e.g., boldface)
		
		// 2. *emphasis* or _emphasis_ (more common)  (e.g., italics)
		// 3. Some text with `some code` inside,
		// 3.1 or indent several lines of code by at least four spaces, as in:
		//
		//	line 1 of code
		//	line 2 of code
		//	line 3 of code
		//
		// Lists
		// The latter option makes Markdown retain all whitespace -- as opposed 
		// to the usual behavoiur, which, by removing line breaks and excess spaces, would break indentation and code layout.
		// * An item in a bulleted (unordered) list
		//     * A subitem, indented with 4 spaces
		// * Another item in a bulleted list
		
		// 1. An item in an enumerated (ordered) list
		//2. Another item in an enumerated list
		
		// # First-level heading
		// #### Fourth-level heading
		
		// Blockquotes
		// > This text will be enclosed in an HTML bloc
		// > Blockquote elements are reflowable. You may arbi
		
		// Links
		// Images
		// Horizontal rules
		
		
		return $Result;
	}
}*/

if (!function_exists('LoadPhpQuery')) {
	function LoadPhpQuery() {
		//if(!class_exists('PhpQuery')) require_once dirname(__FILE__).DS.'vendors' . DS . 'phpQuery.php';
		if (!function_exists('Pq')) require_once USEFULFUNCTIONS_VENDORS . DS . 'phpQuery.php';
	}
}

/**
* Creates phpQuery document from string or file.
* Options: 
* FixHtml (True|False): Clean content by HtmlFormatter
* phpQuery is a server-side, chainable, CSS3 selector driven Document Object Model (DOM),
* API based on jQuery JavaScript Library. 
* More information: http://code.google.com/p/phpquery/
*/ 

if (!function_exists('PqDocument')) {
	function PqDocument($Document, $Options = False) {
		if (!function_exists('Pq')) require_once USEFULFUNCTIONS_VENDORS.'/phpQuery.php';
		if (strpos($Document, '<') === False) {
			if (is_file($Document) || (substr($Document, 0, 7) == 'http://')) {
				$Document = file_get_contents($Document);
			}
		}
		if (ArrayValue('ConvertEncoding', $Options)) $Document = ConvertEncoding($Document);
		
		if (ArrayValue('FixHtml', $Options, True)) {
			$HtmlFormatter = Gdn::Factory('HtmlFormatter');
			if ($HtmlFormatter) $Document = $HtmlFormatter->Format($Document);
		}
		return phpQuery::newDocumentXHTML($Document);
	}
}