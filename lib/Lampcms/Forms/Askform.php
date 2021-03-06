<?php
/**
 *
 * License, TERMS and CONDITIONS
 *
 * This software is lisensed under the GNU LESSER GENERAL PUBLIC LICENSE (LGPL) version 3
 * Please read the license here : http://www.gnu.org/licenses/lgpl-3.0.txt
 *
 *  Redistribution and use in source and binary forms, with or without
 *  modification, are permitted provided that the following conditions are met:
 * 1. Redistributions of source code must retain the above copyright
 *    notice, this list of conditions and the following disclaimer.
 * 2. Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 * 3. The name of the author may not be used to endorse or promote products
 *    derived from this software without specific prior written permission.
 *
 * ATTRIBUTION REQUIRED
 * 4. All web pages generated by the use of this software, or at least
 * 	  the page that lists the recent questions (usually home page) must include
 *    a link to the http://www.lampcms.com and text of the link must indicate that
 *    the website's Questions/Answers functionality is powered by lampcms.com
 *    An example of acceptable link would be "Powered by <a href="http://www.lampcms.com">LampCMS</a>"
 *    The location of the link is not important, it can be in the footer of the page
 *    but it must not be hidden by style attibutes
 *
 * THIS SOFTWARE IS PROVIDED BY THE AUTHOR "AS IS" AND ANY EXPRESS OR IMPLIED
 * WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF
 * MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
 * IN NO EVENT SHALL THE FREEBSD PROJECT OR CONTRIBUTORS BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF
 * THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This product includes GeoLite data created by MaxMind,
 *  available from http://www.maxmind.com/
 *
 *
 * @author     Dmitri Snytkine <cms@lampcms.com>
 * @copyright  2005-2011 (or current year) ExamNotes.net inc.
 * @license    http://www.gnu.org/licenses/lgpl-3.0.txt GNU LESSER GENERAL PUBLIC LICENSE (LGPL) version 3
 * @link       http://www.lampcms.com   Lampcms.com project
 * @version    Release: @package_version@
 *
 *
 */


namespace Lampcms\Forms;

use \Lampcms\Category\Renderer;
use \Lampcms\String\HTMLString;


/**
 * Class responsible
 * for processing the "Ask" form
 *
 *
 * @author Dmitri Snytkine
 *
 */
class Askform extends Form
{


	/**
	 * Name of form template file
	 * The name of actual template should be
	 * set in sub-class
	 *
	 * @var string
	 */
	protected $template = 'tplFormask';

	protected $selected = 0;


	public function __construct(\Lampcms\Registry $R, $selected = 0){
		$this->selected = $selected;
		parent::__construct($R);
	}

	protected function init(){

		/**
		 * This is just an example how a custom validation
		 * callback could be added to form
		 * The callback must accept value of element
		 * and must return error string or true
		 * if there are no errors
		 *
		 * @see Form::init()
		 */
		/*$this->addValidator('title', function($val){

		if(\mb_strlen($val) < 1){
		return 'Title must contain at least 1 character long';
		}

		return true;
		});*/


		/**
		 * Check if category is
		 * optional/required/none
		 * If required then add 3rd param 'true'
		 * also set "Select Category" only
		 * If selected is not passed here
		 */
		$categs = $this->Registry->Ini->CATEGORIES;
		$selectMenu = $clabel = $crequired = null;
		if($categs){
			$Menu = new Renderer($this->Registry);
			$clabel = $this->Registry->Tr->get('Select Category');
			/**
			 * If CATEGORIES in !config.ini is set to 2
			 * then category selection is required.
			 * Adding validator server-side
			 * and HTML5 'required' tag client-side
			 */
			if (2 == $categs){
				$crequired = true;
				$err = $this->Registry->Tr->get('You must select a category');
				$this->addValidator('category', function($val) use ($err){

					if(strlen($val) < 1){
						return $err;
					}

					return true;
				});
			}

			$selectMenu = $Menu->getSelectMenu($this->selected, $clabel, $crequired );
			d('$selectMenu: '.$selectMenu);
		}




		$minTags = $this->Registry->Ini->MIN_QUESTION_TAGS;
		$maxTags = $this->Registry->Ini->MAX_QUESTION_TAGS;

		$d = $this->Tr->get('Please enter between {min_tags} and {max_tags} tags, separated by spaces', array('{min_tags}' => $minTags, '{max_tags}' => $maxTags));
		$this->setVar('tags_d',  $d);
		$this->setVar('tags_l',  $this->Tr['Tags']);
		$this->setVar('Preview', $this->Tr['Preview']);
		$this->setVar('Preview', $this->Tr['Preview']);
		$this->setVar('title_d', $this->Tr['Enter a descriptive title']);
		$this->setVar('title_l', $this->Tr['Title']);
		$this->setVar('submit',  $this->Tr['Ask Question']);
		$this->setVar('category_menu', $selectMenu);
		if(0 === strlen($categs)){
			$this->setVar('category_class', 'hide');
		}
		if($minTags > 0){
			$tagsRequired = '(* %s)';
			$this->setVar('tags_required', sprintf($tagsRequired, 'required'));
		}

		$minTitle = $this->Registry->Ini->MIN_TITLE_CHARS;
		if($minTitle > 0){
			$t = 'Please enter a descriptive title at least %s characters long';
			$this->setVar('title_d', sprintf($t, $minTitle));
		}
	}


	/**
	 * Concrete form validator for this form
	 * (non-PHPdoc)
	 * @see Form::doValidate()
	 */
	protected function doValidate(){

		$this->validateBody()->validateTitle()->validateTags();
	}


	/**
	 * Validate title length
	 *
	 * @return object $this
	 */
	protected function validateTitle(){
		$t = $this->Registry->Request['title'];
		$min = $this->Registry->Ini->MIN_TITLE_CHARS;
		d('min title: '.$min);
		if(\mb_strlen($t) < $min){
			$this->setError('title', 'Title must contain at least '.$min.' letters');
		}

		return $this;
	}


	/**
	 * Validate min number of words in question
	 * and min number of chars in question
	 *
	 * @return object $this
	 */
	protected function validateBody(){

		$minChars = $this->Registry->Ini->MIN_QUESTION_CHARS;
		$minWords = $this->Registry->Ini->MIN_QUESTION_WORDS;
		$body = $this->Registry->Request->getUTF8('qbody');
		$oHtmlString = HTMLString::factory($body);
		$wordCount = $oHtmlString->getWordsCount();
		$len = $oHtmlString->length();

		if($len < $minChars){
			/**
			 * @todo Translate string
			 */
			$this->setError('qbody', 'Question must contain at least '.$minChars.' letters');
		}


		if($wordCount < $minWords){
			/**
			 * @todo Translate string
			 */
			$this->setError('qbody', 'Question must contain at least '.$minWords.' words');
		}

		return $this;
	}


	/**
	 * Validate to enforce at least one tag
	 * and not more that value MAX_QUESTION_TAGS in settings
	 *
	 * @return object $this
	 */
	protected function validateTags(){
		$min = $this->Registry->Ini->MIN_QUESTION_TAGS;
		$max = $this->Registry->Ini->MAX_QUESTION_TAGS;
		$tags = $this->Registry->Request->get('tags', 's', '');
		$tags = \trim($tags);
		/*if(($min > 0) && empty($tags)){
			$this->setError('tags', 'You must include at least one tag');
			}*/

		\mb_regex_encoding('UTF-8');
		$aTags = \mb_split('([\s,;]+)', $tags);
		$count = count($aTags);

		if($count > $max){
			/**
			 * @todo Translate string
			 */
			$this->setError('tags', 'Question cannot have more than '.$max.' tags. Please remove some tags');
		}

		if($count < $min){
			/**
			 * @todo Translate string
			 */
			$this->setError('tags', 'Question must have at least '.$min.' tag(s)');
		}

		return $this;
	}

}
