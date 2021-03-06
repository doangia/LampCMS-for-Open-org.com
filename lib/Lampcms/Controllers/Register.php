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

use \Lampcms\WebPage;
use \Lampcms\String;
use \Lampcms\Cookie;
use \Lampcms\Request;
use \Lampcms\Captcha\Captcha;

/**
 * Main class for creating new account
 * for a new user who is registered
 * with just email address (no 3rd party API login)
 *
 * @todo move as many methods as possible to
 * a wrapper class so that it could be called
 * not only as a web page Conroller
 * but also from the API
 * Later it will be easity to reuse if we have the API
 *
 * @author Dmitri Snytkine
 *
 */
class Register extends WebPage
{
	protected $permission = 'register';


	const EMAIL_BODY = 'Welcome to %1$s!

IMPORTANT: You Must use the link below to activate your account
%4$s

This email contains your login information for %1$s

Your login information is as follows:

    Username: %2$s
    Password: %3$s


You are advised to store the above information in a safe place so that you
do not face any inconvenience in future.

You can change your password after you log in. 


	';

	const SUBJECT = 'Your %s login information';

	/**
	 * Message to show upon completed successful
	 * registration
	 * 
	 * @var string
	 */
	const SUCCESS = 'Welcome to our club.<br>Please check your email and activate your account A.S.A.P.<br>(details are in the email)';

	protected $layoutID = 1;

	/**
	 * Object Regform;
	 *
	 * @var object Forms\Regform
	 */
	protected $Form;

	/**
	 * New temporary password of new user
	 *
	 * @var string
	 */
	protected $pwd;

	/**
	 * Username of new user
	 *
	 * @var string
	 */
	protected $username;

	/**
	 *
	 * Email address of new user
	 * @var string
	 */
	protected $email;

	/**
	 * Object represents on record in EMAILS collection
	 *
	 * @var object of type \Lampcms\Mongo\Doc
	 */
	protected $oEmail;

	protected function main(){
		$this->aPageVars['title'] = $this->_('Create New Account');
		/**
		 * Don't bother with token
		 * for this form.
		 * It uses captcha, so allow
		 * users to submit without token
		 */
		$this->Form = new \Lampcms\Forms\Regform($this->Registry, false);
		$this->Form->setVar('action', 'register');
		/**
		 * Set divID to registration because otherwise
		 * it is default to 'regform' which causes
		 * the whole form's div to be turned into
		 * a modal which is used in quickReg or Join controllers
		 * but for this controller we want a regular web page,
		 * no modals, no Ajax
		 *
		 * Also set className to 'registration' because it defaults
		 * to yui-pre-content which makes the whole div hidden
		 * This is a trick for adding something that later is turned
		 * into modal, but we don't need it for this page
		 */
		$this->Form->setVar('divID', 'registration');
		$this->Form->setVar('className', 'registration');
		$this->Form->setVar('header2', $this->_('Create New Account'));
		$this->Form->setVar('button', '<input name="submit" value="'.$this->_('Register').'" type="submit" class="btn btn-m">');
		$this->Form->setVar('captcha', Captcha::factory($this->Registry->Ini)->getCaptchaBlock());
		$this->Form->setVar('title', $this->_('Create an Account'));
		$this->Form->setVar('titleBar', '');

		if($this->Form->isSubmitted() && $this->Form->validate()){
			$this->getSubmittedValues()
			->createNewUser()
			->createEmailRecord()
			->sendActivationEmail();
			/**
			 * @todo Translate string
			 */
			$this->aPageVars['body'] = '<div id="tools" class="larger">'.self::SUCCESS.'</div>';
		} else {
			$this->aPageVars['body'] = '<div id="userForm" class="frm1">'.$this->Form->getForm().'</div>';
		}
	}


	/**
	 * Init instance variables
	 * $this->username, $this->email and $this->pwd
	 *
	 * @return object $this
	 */
	protected function getSubmittedValues(){
		$this->username = $this->Form->getSubmittedValue('username');;
		$this->pwd = \Lampcms\String::makePasswd();
		$this->email = \mb_strtolower($this->Form->getSubmittedValue('email'));

		return $this;
	}


	/**
	 *
	 * Create new record in USERS collection,
	 *
	 * @return object $this
	 */
	protected function createNewUser(){

		$coll = $this->Registry->Mongo->USERS;
		$coll->ensureIndex(array('username_lc' => 1), array('unique' => true));
		/**
		 * Cannot make email unique index because external users
		 * don't have email, and then value counts as null
		 * and multiple null values count as duplicate!
		 *
		 */
		$coll->ensureIndex(array('email' => 1));
		$coll->ensureIndex(array('role' => 1));
		/**
		 * Indexes for managing 3 types
		 * of following
		 */
		$coll->ensureIndex(array('a_f_t' => 1));
		$coll->ensureIndex(array('a_f_u' => 1));
		$coll->ensureIndex(array('a_f_q' => 1));

		$sid = \Lampcms\Cookie::getSidCookie();

		$aData['username'] 		= $this->username;
		$aData['username_lc'] 	= strtolower($this->username);
		$aData['email'] 		= $this->email;
		$aData['rs'] 			= (false !== $sid) ? $sid : \Lampcms\String::makeSid();
		$aData['role'] 			= $this->getRole();
		$aData['tz'] 			= \Lampcms\TimeZone::getTZbyoffset($this->Request->get('tzo'));
		$aData['pwd'] 			= String::hashPassword($this->pwd);
		$aData['i_reg_ts'] 		= time();
		$aData['date_reg']		= date('r');
		$aData['i_fv'] 			= (false !== $intFv = \Lampcms\Cookie::getSidCookie(true)) ? $intFv : time();
		$aData['lang'] 			= $this->Registry->getCurrentLang();
		$aData['locale'] 		= $this->Registry->Locale->getLocale();
		/**
		 * Initial reputation is always 1
		 * @var int
		 */
		$aData['i_rep'] = 1;
		$aUser = array_merge($this->Registry->Geo->Location->data, $aData);

		d('aUser: '.print_r($aUser, 1));

		$User = \Lampcms\User::factory($this->Registry, $aUser);
		$User->save();
		d('id: '.$User['_id']);
		
		$this->processLogin($User);

		\Lampcms\PostRegistration::createReferrerRecord($this->Registry, $User);


		return $this;
	}


	/**
	 * Normally the role of newly registered user
	 * is 'unactivated' unless
	 * the email address matches that of the EMAIL_ADMIN
	 * in settings, in which case the account will
	 * automatically become an administrator account
	 *
	 *
	 * @param string $email email address
	 */
	protected function getRole(){

		return ($this->Registry->Ini->EMAIL_ADMIN === $this->email) ? 'administrator' : 'unactivated';
	}


	/**
	 * Created a new record in EMAILS collection
	 *
	 * @return object $this
	 */
	protected function createEmailRecord(){

		$coll = $this->Registry->Mongo->EMAILS;
		$coll->ensureIndex(array('email' => 1), array('unique' => true));

		$a = array(
			'email' => $this->email,
			'i_uid' => $this->Registry->Viewer->getUid(),
			'has_gravatar' => \Lampcms\Gravatar::factory($this->email)->hasGravatar(),
			'ehash' => hash('md5', $this->email),
			'i_code_ts' => time(),
			'code' => \substr(hash('md5', \uniqid(\mt_rand())), 0, 12));
		
		$this->oEmail = \Lampcms\Mongo\Doc::factory($this->Registry, 'EMAILS', $a);
		
		$res = $this->oEmail->save();
		d('$res: '.$res);
		
		return $this;
	}


	/**
	 * Make account activation link
	 *
	 * @return string url of account activation link
	 */
	protected function makeActivationLink(){
		$tpl = $this->Registry->Ini->SITE_URL.'/aa/%d/%s';
		$link = \sprintf($tpl, $this->oEmail['_id'], $this->oEmail['code']);
		d('activation link: '.$link);

		return $link;
	}


	protected function sendActivationEmail(){
		$sActivationLink = $this->makeActivationLink();
		$siteName = $this->Registry->Ini->SITE_NAME;
		$body = vsprintf(self::EMAIL_BODY, array($siteName, $this->username, $this->pwd, $sActivationLink));
		$subject = sprintf(self::SUBJECT, $this->Registry->Ini->SITE_NAME);

		$this->Registry->Mailer->mail($this->email, $subject, $body);

		return $this;
	}

}
