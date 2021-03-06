<?php
/**
 * An extension to memberprofiles module. This will a new tab in memberprofile content
 * tab 
 *   
 * @package		silverstripe-clickbank
 * @subpackage	extensions
 * @author 		Richard Sentino <rix@mindginative.com>
 */
class MemberProfilePageExtension extends DataObjectDecorator {
	public function extraStatics() {
		return array (
			'db' => array (
				'ClickBankPageTitle'   => 'Varchar(255)',
				'ClickBankPageContent' => 'HTMLText',
				'ClickBankEnableProfile' => 'Boolean'
			),
			'many_many' => array (
				'ClickBankGroups' => 'Group'
			)
		);
	}
	
	/**
	 * Displays new ClickBank tab in MemberProfile CMS field
	 * 
	 * @return	Object	ClickBank Tab
	 */
	public function updateCMSFields(FieldSet &$fields) {
		// profile page
		$fields->addFieldToTab('Root.Content.ClickBankProfile', new TextField('ClickBankPageTitle', _t('ClickBank.PROFILE_CMS_PROFILE_TITLE', 'Title')));
		$fields->addFieldToTab('Root.Content.ClickBankProfile', new HtmlEditorField('ClickBankPageContent', _t('ClickBank.PROFILE_CMS_PROFILE_CONTENT','Content')));
		$fields->addFieldToTab('Root.Content.ClickBankProfile', new LabelField('ReplacementTextHeader', _t('ClickBank.PROFILE_CMS_PROFILE_REPLACEMENTS_HEADER', 'Replacements: $CBReceipt = Last ClickBank transaction receipt')));
		$fields->addFieldToTab('Root.Content.ClickBankProfile', new CheckboxField('ClickBankEnableProfile', _t('ClickBank.PROFILE_CMS_PROFILE_ENABLE', 'Enable ClickBank Profile Page')));

		// clickbank groups
		$fields->findOrMakeTab('Root.Profile.Groups');
		$fields->addFieldToTab('Root.Profile.Groups', new HeaderField('ClickBankGroupsHeader', _t('ClickBank.PROFILE_CMS_CBGROUP_HEADER','ClickBank Group Assignment')));
		$fields->addFieldToTab('Root.Profile.Groups', new LiteralField('ClickBankGroupsLiteralField', _t('ClickBank.PROFILE_CMS_CBGROUP_NOTE_LF', 'Any users buying the product through ClickBank will always be added to the groups below.')));
		$fields->addFieldToTab('Root.Profile.Groups', new CheckboxSetField('ClickBankGroups', '', DataObject::get('Group')->map()));
	}
	
	/**
	 * Returns a list of groups available for regular members
	 * 
	 * @see		MemberProfilePage_Controller::getSettableGroupIdsFrom
	 * @param	none
	 * @return	Array	of Group IDs 
	 */
	public function getMemberProfileGroups() {
		$groupIds = array();
		$groups = $this->owner->Groups()->column('ID');
		if (!empty($groups)) {
			foreach ($groups as $groupId) {
				$groupIds[] = $groupId;
			}
			return $groupIds;
		}
		return false;
	}
	
	/**
	 * Returns a list of groups available for ClickBank user group
	 * 
	 * @see		MemberProfilePage_Controller::getSettableGroupIdsFrom
	 * @param	none
	 * @return	Array	of group IDs 
	 */	
	public function getClickBankMemberGroups() {
		$groupIds = array();
		$groups = $this->owner->ClickBankGroups()->column('ID');
		if (!empty($groups)) {
			foreach ($groups as $groupId) {
				$groupIds[] = $groupId;
			}
			return $groupIds;
		}
		return false;		
	}
}

/**
 * MemberProfilePage_Controller extension
 */
class MemberProfilePageExtension_Controller extends Extension {
	public static $allowed_actions = array (
		'clickbankProfile',
		'ClickBankProfileForm'
	);
	
	/**
	 * Display ClickBank profile.
	 * 
	 * @see		MemberProfilePage_Controller::indexProfile()
	 * @param	none
	 * @return	mixed
	 */
	public function clickbankProfile() {
		if (!$this->owner->AllowProfileEditing) {
			return Security::permissionFailure($this->owner, _t(
				'MemberProfiles.CANNOTEDIT',
				'You cannot edit your profile via this page.'
			));
		}
		
		$member = Member::currentUser();
		
		foreach($this->owner->Groups() as $group) {
			if(!$member->inGroup($group)) {
				return Security::permissionFailure($this->owner);
			}
		}
		
		$data = array (
			'Title' => $this->owner->obj('ClickBankPageTitle'),
			'Content' => $this->owner->obj('ClickBankPageContent')
		);
		
		return $this->owner->customise($data)->renderWith(array('ClickBankMemberProfilePage', 'Page'));
	}
	
	/**
	 * Displays ClickBank profile
	 * 
	 * @todo	Temp. readonly mode
	 * @param	none
	 * @return	Object	Form
	 */
	public function ClickBankProfileForm() {
		$member = Member::currentUser();
		$clickBankProfile = $member->ClickBankProfile(); 
		
		// address
		$ccustaddr1 = new ReadonlyField('ccustaddr1', _t('ClickBank.PROFILE_ADDRESS_1'), $clickBankProfile->ccustaddr1);
		$ccustaddr2 = new ReadonlyField('ccustaddr2', _t('ClickBank.PROFILE_ADDRESS_2'), $clickBankProfile->ccustaddr2);
		$ccustcity = new ReadonlyField('ccustcity', _t('ClickBank.PROFILE_CITY'), $clickBankProfile->ccustcity);
		$ccustcounty = new ReadonlyField('ccustcounty', _t('ClickBank.PROFILE_COUNTY'), $clickBankProfile->ccustcounty);
		$ccuststate = new ReadonlyField('ccuststate', _t('ClickBank.PROFILE_STATE'), $clickBankProfile->ccuststate);
		$ccustzip = new ReadonlyField('ccustzip', _t('ClickBank.PROFILE_ZIP'), $clickBankProfile->ccustzip);
		$ccustcc = new ReadonlyField('ccustcc', _t('ClickBank.PROFILE_COUNTRY'), $clickBankProfile->ccustcc);
		 
		// shipping address
		$ccustshippingstate = new ReadonlyField('ccustshippingstate', _t('ClickBank.PROFILE_SHIPPING_STATE'), $clickBankProfile->ccustshippingstate);
		$ccustshippingzip = new ReadonlyField('ccustshippingzip', _t('ClickBank.PROFILE_SHIPPING_ZIP'), $clickBankProfile->ccustshippingzip);
		$ccustshippingcountry = new ReadonlyField('ccustshippingcountry', _t('ClickBank.PROFILE_SHIPPING_COUNTRY'), $clickBankProfile->ccustshippingcountry);
		
		$fields = new FieldSet(
			new HeaderField('AddressHeader', _t('ClickBank.ADDRESS_HEADER')),
			$ccuststate,
			$ccustzip,
			$ccustcc,
			
			new HeaderField('ShippingAddressHeader', _t('ClickBank.SHIPPING_ADDRESS_HEADER')),
			$ccustshippingstate,
			$ccustshippingzip,
			$ccustshippingcountry,
			
			new LiteralField('', '<br />')
		);
		
		$actions = new FieldSet(
			new FormAction('doClickBankProfileForm', _t('ClickBank.RETURN_MAIN_BUTTON'))
		);
		
		return new Form($this->owner, 'ClickBankProfileForm', $fields, $actions);
	}
	
	/**
	 * Updates an existing ClickBank Member's profile.
	 *
	 * @todo	temp. redirect to main member's profile page.
	 */
	public function doClickBankProfileForm() {
		return Director::redirect($this->owner->Link());
	}
}