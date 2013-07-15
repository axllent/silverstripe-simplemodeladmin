# Simple ModelAdmin for SilverStripe 3

This is a "trimmed down" version of Silverstripe's ModelAdmin, the differences being:
* Import, Export & Print functionality has been removed
* Filtering has been removed
* By default, column sorting has been disabled

## Requirements
* SilverStripe 3.0+

## Usage
<pre>
class MyDataObject_ModelAdmin extends SimpleModelAdmin {
	...
	public function canView() {
		return Permission::check('CMS_ACCESS_CMSMain', 'any', $member);
	}
}
</pre>

## Note
Whilst ModelAdmin does inherit the model permissions, your extension of the SimpleModelAdmin
requires it's own canView permissions as this does not add a separate entity into the CMS
Access security section.