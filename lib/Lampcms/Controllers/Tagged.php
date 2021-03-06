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

namespace Lampcms\Controllers;

use \Lampcms\Relatedtags;

/**
 * Controller for rendering page
 * with list of questions for specific tag or tags
 *
 *
 * @author Dmitri Snytkine
 *
 */
class Tagged extends Unanswered
{
	protected $aRequired = array('tags');

	/**
	 * Indicates the current tab
	 *
	 * @var string
	 */
	protected $qtab = 'questions';


	/**
	 * Select items according to conditions passed in GET
	 * Conditions can be == 'unanswered', 'hot', 'recent' (default)
	 */
	protected function getCursor(){

		/**
		 * Must call getTags() before
		 * using this->rawTags because
		 * it is set inside the getTags() method
		 *
		 *
		 */
		$this->aTags = $this->getTags();
		$this->pagerPath = '/tagged/'.$this->rawTags;
		d('aTags: '.print_r($this->aTags, 1));

		$aFields = array();

		$cond = $this->Request->get('cond', 's', 'recent');
		
		/**
		 * Default sort is by timestamp Descending
		 * meaning most recent should be on top
		 *
		 */
		$sort = array('i_ts' => -1);

		$where = array('a_tags' => array('$all' => $this->aTags) );
		$where['i_del_ts'] = null;

		$this->counterTaggedText = \tplCounterblocksub::parse(array(str_replace(' ', ' + ', $this->tags), $this->_('Tagged')), false);

		$this->Cursor = $this->Registry->Mongo->QUESTIONS->find($where, $this->aFields);
		$this->count = $this->Cursor->count(true);
		$this->Cursor->sort($sort);

		return $this;
	}



	protected function makeRecentTags(){

		/**
		 * @todo
		 * Currently we are finding related tags for just one tag
		 * This is very fast because we store related tags pre-parsed
		 * as html string. This works and looks good but...
		 * If we really wanted this to work just like
		 * on stackoverflow then we have to change it to this:
		 * if there is only one tag passed in URL, then use this method
		 * otherwise do this:
		 * Use find() on RELATED_TAGS collection and pass $this->aTags
		 * as $all condition. This will find all records in RELATED_TAGS
		 * that have all these tags. Then we have result cursor from which
		 * we are only interested in _id value which is the actual tag related
		 * to all our tags.
		 * We would then loop over cursor and pass
		 * each array to tplRelatedlink, passing each _id
		 * as value of 'tag' and prepend +tag to $this->rawTags as
		 * value of 'link'
		 *
		 * This will be slower than just using pre-parsed related tags
		 * but it will be exactly like on Stackoverflow
		 *
		 * For this to work we need to ensureIndex on 'tags' in RELATED_TAGS
		 * collection!
		 */
		$tag = end($this->aTags);
		d('tag: '.$tag);

		$s = Relatedtags::factory($this->Registry)->getHtml($tag);

		if(!empty($s)){
			$tags = \tplBoxrecent::parse(array('tags' => $s, 'title' => $this->_('Related Tags')));
			$this->aPageVars['tags'] = $tags;
		}

		$this->makeFollowTagButton();

		return $this;
	}

}

